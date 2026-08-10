    <script>
        (function () {
            var TEMPLATES = @json($templates);
            var BATCH = {{ \App\Http\Controllers\Vendor\WhatsAppController::BULK_BATCH_LIMIT }};
            var RECIPIENTS_URL = '{{ route('vendor.whatsapp.bulk.recipients') }}';
            var SEND_URL = '{{ route('vendor.whatsapp.bulk.send') }}';
            var HISTORY_URL = '{{ route('vendor.whatsapp.bulk.history') }}';
            var CSRF = '{{ csrf_token() }}';

            var PLATFORM_MAX = {{ $platformUserCount }};
            var RATE = @json($rates);
            var CURRENCY = '{{ \App\CentralLogics\Helpers::currency_symbol() }}';

            var selected = new Set();
            var loaded = [];
            var searchTimer = null;

            // Built by concatenation so Blade never sees a literal double-brace in this script.
            var OPEN = '{' + '{', CLOSE = '}' + '}';

            var $tpl = document.getElementById('wb-template');
            var $vars = document.getElementById('wb-vars');
            var $preview = document.getElementById('wb-preview');
            var $previewBody = document.getElementById('wb-preview-body');
            var $list = document.getElementById('wb-clients');
            var $search = document.getElementById('wb-search');
            var $count = document.getElementById('wb-selected-count');
            var $truncated = document.getElementById('wb-truncated');
            var $send = document.getElementById('wb-send');
            var $progress = document.getElementById('wb-progress');
            var $bar = document.getElementById('wb-progress-bar');
            var $ptext = document.getElementById('wb-progress-text');
            var $results = document.getElementById('wb-results');
            var $summary = document.getElementById('wb-summary');
            var $pillClients = document.getElementById('wb-pill-clients');
            var $pillPlatform = document.getElementById('wb-pill-platform');

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            function currentTemplate() {
                return $tpl.value === '' ? null : TEMPLATES[parseInt($tpl.value, 10)];
            }

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
                // Older cached payloads only carried a count.
                var out = [], n = (t && t.var_count) || 0;
                for (var i = 1; i <= n; i++) out.push({ key: String(i), label: 'Variable ' + i, auto: false });
                return out;
            }

            // A real recipient out of the current selection, so the preview reads the way the
            // first person will actually receive it instead of showing placeholders. Platform
            // recipients are anonymous to the vendor, so there is nothing to sample there.
            function sampleClient() {
                // Whichever tab is open — a ticked customer is still the truest preview of how
                // the message reads, and platform recipients are anonymous so they offer none.
                for (var i = 0; i < loaded.length; i++) {
                    if (selected.has(loaded[i].id)) return loaded[i];
                }
                return null;
            }

            function renderPreview() {
                var t = currentTemplate();
                if (!t) { $preview.style.display = 'none'; return; }

                // Auto slots resolve to the {name} / {phone} markers first so the pass below
                // can bold them, whatever the vendor typed elsewhere.
                var body = t.body;
                paramValues().forEach(function (p) {
                    var slot = OPEN + p.key + CLOSE;
                    var shown = p.key === 'customer_name' ? '{name}'
                        : p.key === 'customer_phone' ? '{phone}'
                        : (p.value || slot);
                    body = body.split(slot).join(shown);
                });
                var c = sampleClient();
                $previewBody.innerHTML = esc(body)
                    .replace(/\{name\}/g, '<b>' + esc((c && c.f_name) || 'each customer’s name') + '</b>')
                    .replace(/\{(customer_)?phone\}/g, '<b>' + esc((c && c.phone) || 'each customer’s number') + '</b>');
                $preview.style.display = 'block';
            }

            /**
             * Templates greet with a variable ("Hi" followed by the first token) and label the
             * contact slot ("Phone: " followed by a token) — prefill those with {name} / {phone}
             * so the vendor never has to know the tokens exist. The greeting test is anchored to
             * the start of a line, and the phone test only fires on a label right before the
             * token, so a variable buried mid-sentence is left alone.
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
                    'with each recipient’s own name and number — use them in any variable that should be ' +
                    'personalised.';
                $vars.appendChild(help);

                vars.forEach(function (v) {
                    var wrap = document.createElement('div');
                    wrap.className = 'form-group mb-2';

                    if (v.auto) {
                        // Filled from the recipient row on the server — showing an editable box
                        // would only invite a value that gets thrown away.
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

            function countFrom(inputId, max) {
                var $input = document.getElementById(inputId);
                if (!$input) return 0;
                var n = parseInt($input.value, 10);
                if (isNaN(n) || n < 1) return 0;
                return Math.min(n, max);
            }

            function platformCount() {
                return countFrom('wb-platform-count', PLATFORM_MAX);
            }

            // Everyone this Send covers — both audiences added together, because one press now
            // sends to both rather than to whichever tab happened to be open.
            function recipientCount() {
                return selected.size + platformCount();
            }

            function money(n) {
                return CURRENCY + (Math.round(n * 100) / 100).toFixed(2);
            }

            function renderSummary() {
                var own = selected.size, plat = platformCount();
                if (!own && !plat) { $summary.style.display = 'none'; return; }

                var parts = [];
                if (own) parts.push('<b>' + own + '</b> of your customers');
                if (plat) parts.push('<b>' + plat + '</b> MyChitti user' + (plat === 1 ? '' : 's'));

                var cost = own * (RATE.own || 0) + plat * (RATE.platform || 0);

                // Always name the rate behind the total. The two audiences are priced differently,
                // so a bare figure leaves the vendor unable to tell why adding MyChitti users moved
                // it as much as it did.
                var rates = [];
                if (own) rates.push(money(RATE.own) + ' × ' + own + ' own');
                if (plat) rates.push(money(RATE.platform) + ' × ' + plat + ' MyChitti');

                $summary.innerHTML = 'This send goes to ' + parts.join(' <span class="text-muted">and</span> ') +
                    ' — <b>' + (own + plat) + '</b> message' + (own + plat === 1 ? '' : 's') + ' in one go.' +
                    '<div class="text-muted mt-1">Costs about <b>' + money(cost) + '</b> from your wallet' +
                    (rates.length ? ' (' + rates.join(' + ') + ')' : '') +
                    ', GST included.</div>';
                $summary.style.display = 'block';
            }

            function syncSend() {
                renderPreview();
                var t = currentTemplate();
                var filled = !t || paramValues().every(function (p) { return p.auto || p.value.trim() !== ''; });
                var own = selected.size, plat = platformCount(), n = own + plat;

                // A media template cannot go out without its file — the whole batch would come
                // back as error 132012, so the button stays down until the upload finishes.
                var mediaReady = !t || !t.needs_media || !!mediaUrl;

                $send.disabled = !t || !filled || !mediaReady || n === 0;

                // Each pill shows what is chosen from it, so a selection on the tab that is out of
                // sight can never be forgotten about — or sent by surprise.
                $pillClients.textContent = own ? own + ' / {{ $clientCount }}' : '{{ $clientCount }}';
                $pillPlatform.textContent = plat ? plat + ' / {{ $platformUserCount }}' : '{{ $platformUserCount }}';

                $count.textContent = n
                    ? n + ' selected' + (own && plat ? ' (' + own + ' + ' + plat + ')' : '')
                    : '0 selected';
                $send.textContent = n
                    ? 'Send to ' + n + ' recipient' + (n === 1 ? '' : 's')
                    : 'Send';

                renderSummary();
            }

            // Switches which picker is on screen. It does NOT choose an audience any more —
            // whatever is selected on both tabs goes out together on one Send.
            function setMode(next) {
                Array.prototype.forEach.call(document.querySelectorAll('.wb-mode'), function (el) {
                    el.classList.toggle('active', el.dataset.mode === next);
                });
                document.getElementById('wb-pane-clients').style.display = next === 'clients' ? 'block' : 'none';
                document.getElementById('wb-pane-platform').style.display = next === 'platform' ? 'block' : 'none';
                syncSend();
            }

            function renderClients() {
                if (!loaded.length) {
                    $list.innerHTML = '<div class="text-muted text-center p-3" style="font-size:13px;">No clients match.</div>';
                    return;
                }
                $list.innerHTML = loaded.map(function (c) {
                    return '<label class="d-flex align-items-center px-3 py-2 mb-0 border-bottom" style="cursor:pointer;gap:10px;">' +
                        '<input type="checkbox" class="wb-client" value="' + c.id + '"' + (selected.has(c.id) ? ' checked' : '') + '>' +
                        '<span style="font-size:13px;"><b>' + esc(c.f_name || 'Unnamed') + '</b> ' +
                        '<span class="text-muted">' + esc(c.phone) + '</span></span></label>';
                }).join('');

                Array.prototype.forEach.call($list.querySelectorAll('.wb-client'), function (box) {
                    box.addEventListener('change', function () {
                        var id = parseInt(this.value, 10);
                        this.checked ? selected.add(id) : selected.delete(id);
                        syncSend();
                    });
                });
            }

            function loadClients() {
                $list.innerHTML = '<div class="text-muted text-center p-3" style="font-size:13px;">Loading clients…</div>';
                fetch(RECIPIENTS_URL + '?search=' + encodeURIComponent($search.value), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    loaded = d.clients || [];
                    if (d.truncated) {
                        $truncated.style.display = 'block';
                        $truncated.textContent = 'Showing the first ' + loaded.length + ' of ' + d.total + ' clients — search to narrow the list.';
                    } else {
                        $truncated.style.display = 'none';
                    }
                    renderClients();
                    syncSend();
                })
                .catch(function () {
                    $list.innerHTML = '<div class="text-danger text-center p-3" style="font-size:13px;">Could not load clients.</div>';
                });
            }

            function sendBatches() {
                var t = currentTemplate();
                var total = recipientCount();
                var batches = [];

                // One id for the whole send — across BOTH audiences. The server claims each
                // recipient against it before dispatching, so a batch that is retried - or a whole
                // run started again after a break - skips anyone already messaged instead of
                // messaging them twice. Sharing the id between the two audiences is also what
                // stops someone who sits in the vendor's book *and* the platform pool being
                // messaged once as each: the claim is on the number, not on the list it came from.
                var runId = (window.crypto && crypto.randomUUID)
                    ? crypto.randomUUID()
                    : 'r' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);

                // Own customers first. They are the cheaper audience and the relationship the
                // vendor actually has, so if a wallet runs dry mid-run it should run dry on
                // strangers, not on the people who already buy from them.
                var ids = Array.from(selected);
                for (var i = 0; i < ids.length; i += BATCH) {
                    batches.push({ mode: 'clients', client_ids: ids.slice(i, i + BATCH) });
                }

                // No offset: the server excludes everyone already reached and everyone
                // already claimed in this run, so each batch returns the next unmessaged
                // people. An offset walk restarted at zero on every send, which is why the
                // same lowest-numbered people were reached over and over.
                var plat = platformCount();
                for (var o = 0; o < plat; o += BATCH) {
                    batches.push({ mode: 'platform', limit: Math.min(BATCH, plat - o) });
                }

                var done = 0, sent = 0, skipped = 0, failures = [];
                $send.disabled = true;
                $progress.style.display = 'block';
                $results.style.display = 'none';

                function batchSize(b) {
                    return b.mode === 'clients' ? b.client_ids.length : b.limit;
                }

                function step(index, attempt) {
                    attempt = attempt || 0;
                    if (index >= batches.length) {
                        $ptext.textContent = 'Finished — ' + sent + ' sent, ' + failures.length + ' failed'
                            + (skipped ? ', ' + skipped + ' already messaged' : '') + '.';
                        showResults(sent, skipped, failures);
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
                            (res.d.results || []).forEach(function (r) { if (!r.success) failures.push(r); });
                        }
                        done += batchSize(batches[index]);
                        var pct = Math.round((done / total) * 100);
                        $bar.style.width = pct + '%';
                        $ptext.textContent = done + ' of ' + total + ' processed…';
                        step(index + 1);
                    })
                    .catch(function () {
                        // Retrying a lost request is safe because the server claims each
                        // recipient before dispatching: anyone the first attempt already
                        // messaged is claimed, so they come back as skipped, not as a duplicate.
                        if (attempt < 1) {
                            step(index, attempt + 1);
                            return;
                        }
                        failures.push({ name: '—', phone: '—', error: 'Network error on a batch of ' + batchSize(batches[index]) + '. Nobody in it was messaged twice — send again to cover them.' });
                        done += batchSize(batches[index]);
                        step(index + 1);
                    });
                }
                step(0, 0);
            }

            function showResults(sent, skipped, failures) {
                var html = '<div class="alert ' + (failures.length ? 'alert-warning' : 'alert-success') + '" style="font-size:13px;">' +
                    '<b>' + sent + '</b> message' + (sent === 1 ? '' : 's') + ' sent' +
                    (failures.length ? ', <b>' + failures.length + '</b> failed' : '') +
                    (skipped ? ', <b>' + skipped + '</b> skipped (already messaged in this run)' : '') + '.</div>';

                if (failures.length) {
                    html += '<div class="border rounded" style="max-height:200px;overflow-y:auto;">' +
                        failures.map(function (f) {
                            return '<div class="px-3 py-2 border-bottom" style="font-size:12px;">' +
                                '<b>' + esc(f.name) + '</b> <span class="text-muted">' + esc(f.phone) + '</span><br>' +
                                '<span class="text-danger">' + esc(f.error) + '</span></div>';
                        }).join('') + '</div>';
                }

                // Where the full list of numbers lives — the results box only keeps what failed,
                // and only until the page is reloaded.
                html += '<div class="mt-2"><a href="' + HISTORY_URL + '" class="btn btn-sm btn-outline-secondary">' +
                    '<i class="tio-history"></i> See every number this went to</a></div>';

                $results.innerHTML = html;
                $results.style.display = 'block';
            }

            // ---- Media header ---------------------------------------------------------------
            // Templates approved with an image / video / document at the top need that file on
            // every message. It is uploaded once per send and reused for the whole run.
            var MEDIA_URL = '{{ route('vendor.whatsapp.bulk.header-media') }}';
            var $media = document.getElementById('wb-media');
            var $mediaFile = document.getElementById('wb-media-file');
            var $mediaLabel = document.getElementById('wb-media-label');
            var $mediaStatus = document.getElementById('wb-media-status');
            var $mediaPreview = document.getElementById('wb-media-preview');
            var mediaUrl = '';

            var MEDIA_ACCEPT = {
                IMAGE: 'image/jpeg,image/png',
                VIDEO: 'video/mp4',
                DOCUMENT: 'application/pdf'
            };

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
                Array.prototype.forEach.call(document.querySelectorAll('.wb-media-kind'), function (el) {
                    el.textContent = kind;
                });
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

            $tpl.addEventListener('change', function () {
                syncMedia();
                renderVars();
            });
            $search.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(loadClients, 300);
            });
            document.getElementById('wb-select-all').addEventListener('click', function () {
                loaded.forEach(function (c) { selected.add(c.id); });
                renderClients();
                syncSend();
            });
            document.getElementById('wb-clear').addEventListener('click', function () {
                selected.clear();
                renderClients();
                syncSend();
            });
            Array.prototype.forEach.call(document.querySelectorAll('.wb-mode'), function (el) {
                el.addEventListener('click', function () { setMode(this.dataset.mode); });
            });
            ['wb-platform-count'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.addEventListener('input', syncSend);
            });
            $send.addEventListener('click', function () {
                // Spelled out per audience: the confirm is the last chance to notice that the
                // other tab still has people on it.
                var own = selected.size, plat = platformCount(), parts = [];
                if (own) parts.push(own + ' of your customers');
                if (plat) parts.push(plat + ' MyChitti user' + (plat === 1 ? '' : 's'));

                if (!confirm('Send this template to ' + parts.join(' and ') + '?')) return;
                sendBatches();
            });

            loadClients();
        })();
    </script>
