    <script>
        (function () {
            var TEMPLATES = @json($templates);
            var RECIPIENTS_URL = '{{ route('vendor.whatsapp.bulk.recipients') }}';
            var SEND_URL = '{{ route('vendor.whatsapp.bulk.send') }}';
            var HISTORY_URL = '{{ route('vendor.whatsapp.bulk.history') }}';
            // Built with a placeholder id: the run being watched changes, the route does not.
            var PROGRESS_URL = '{{ route('vendor.whatsapp.bulk.progress', ['runId' => '__RUN__']) }}';
            var STOP_URL = '{{ route('vendor.whatsapp.bulk.stop', ['runId' => '__RUN__']) }}';
            // A send already in flight when this page loaded. It belongs to the store, not to the
            // tab that started it, so any reopened composer picks it back up.
            var RUNNING = @json($activeRun->run_id ?? null);
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
            var $stopWrap = document.getElementById('wb-stop-wrap');
            var $stop = document.getElementById('wb-stop');
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

            // ---- sending --------------------------------------------------------------------
            // The composer no longer drives the send. One POST books the run, the queue works
            // through it, and everything below is only watching — close the tab and the messages
            // keep going out.
            var pollTimer = null;
            var activeRun = null;

            function startRun() {
                var t = currentTemplate();

                $send.disabled = true;
                $progress.style.display = 'block';
                $results.style.display = 'none';
                $ptext.textContent = 'Starting…';

                fetch(SEND_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        template: t.name,
                        language: t.language,
                        params: paramValues(),
                        header_media: mediaUrl,
                        client_ids: Array.from(selected),
                        platform_limit: platformCount()
                    })
                })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                .then(function (res) {
                    if (!res.ok) {
                        // A run already in flight is not an error worth throwing away — attach to
                        // it instead, which is what the vendor wanted by pressing Send anyway.
                        if (res.d && res.d.run_id) {
                            watch(res.d.run_id);
                            return;
                        }
                        $progress.style.display = 'none';
                        $send.disabled = false;
                        $results.innerHTML = '<div class="alert alert-danger" style="font-size:13px;">' +
                            esc((res.d && res.d.message) || 'That send could not be started.') + '</div>';
                        $results.style.display = 'block';
                        return;
                    }
                    watch(res.d.run_id);
                })
                .catch(function () {
                    $progress.style.display = 'none';
                    $send.disabled = false;
                    $results.innerHTML = '<div class="alert alert-danger" style="font-size:13px;">' +
                        'Could not reach the server. Nothing was sent — try again.</div>';
                    $results.style.display = 'block';
                });
            }

            // Follow a run that is already going: after a page reload, or after a Send. Polling
            // rather than holding a request open, so a dropped connection costs nothing.
            function watch(runId) {
                // Never two pollers on one run — a Send pressed while an earlier run was still
                // being followed would otherwise leave both timers ticking.
                clearTimeout(pollTimer);
                activeRun = runId;
                $send.disabled = true;
                $progress.style.display = 'block';
                $stopWrap.style.display = 'inline-block';
                poll();
            }

            function poll() {
                if (!activeRun) return;

                fetch(PROGRESS_URL.replace('__RUN__', encodeURIComponent(activeRun)), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.success) { throw new Error(d.message || 'gone'); }
                    render(d);

                    if (d.finished) {
                        activeRun = null;
                        $stopWrap.style.display = 'none';
                        $send.disabled = false;
                        syncSend();
                        return;
                    }
                    pollTimer = setTimeout(poll, 3000);
                })
                .catch(function () {
                    // A lost poll says nothing about the send, which is running on the server —
                    // keep asking rather than reporting a failure that has not happened.
                    pollTimer = setTimeout(poll, 6000);
                });
            }

            function render(d) {
                var done = d.sent + d.failed;
                var pct = d.total ? Math.round((done / d.total) * 100) : 0;
                $bar.style.width = pct + '%';

                if (!d.finished) {
                    $ptext.textContent = d.status === 'queued'
                        ? 'Queued — starting shortly. You can close this page; sending carries on.'
                        : done + ' of ' + d.total + ' sent — this carries on in the background, even if you close this page.';
                    return;
                }

                $bar.style.width = '100%';
                $ptext.textContent = 'Finished — ' + d.sent + ' sent, ' + d.failed + ' failed'
                    + (d.skipped ? ', ' + d.skipped + ' not sent' : '') + '.';
                showResults(d);
            }

            function showResults(d) {
                var stopped = d.status !== 'done';
                var html = '<div class="alert ' + ((d.failed || stopped) ? 'alert-warning' : 'alert-success') + '" style="font-size:13px;">' +
                    '<b>' + d.sent + '</b> message' + (d.sent === 1 ? '' : 's') + ' sent' +
                    (d.failed ? ', <b>' + d.failed + '</b> failed' : '') +
                    (d.skipped ? ', <b>' + d.skipped + '</b> skipped (already messaged in this run, or no longer reachable)' : '') + '.' +
                    (stopped && d.message ? '<div class="mt-1">' + esc(d.message) + '</div>' : '') +
                    (stopped && d.pending ? '<div class="mt-1">' + d.pending + ' recipient' + (d.pending === 1 ? ' was' : 's were') +
                        ' not messaged.</div>' : '') + '</div>';

                if (d.failures && d.failures.length) {
                    html += '<div class="border rounded" style="max-height:200px;overflow-y:auto;">' +
                        d.failures.map(function (f) {
                            return '<div class="px-3 py-2 border-bottom" style="font-size:12px;">' +
                                '<b>' + esc(f.name) + '</b> <span class="text-muted">' + esc(f.phone) + '</span><br>' +
                                '<span class="text-danger">' + esc(f.error) + '</span></div>';
                        }).join('') + '</div>';
                    if (d.failed > d.failures.length) {
                        html += '<small class="text-muted d-block mt-1">Showing the last ' + d.failures.length +
                            ' of ' + d.failed + ' failures — the full list is in the history.</small>';
                    }
                }

                // Where the full list of numbers lives — the results box only keeps what failed,
                // and only until the page is reloaded.
                html += '<div class="mt-2"><a href="' + HISTORY_URL + '" class="btn btn-sm btn-outline-secondary">' +
                    '<i class="tio-history"></i> See every number this went to</a></div>';

                $results.innerHTML = html;
                $results.style.display = 'block';
            }

            $stop.addEventListener('click', function () {
                if (!activeRun) return;
                if (!confirm('Stop this send? Anyone already messaged stays messaged; nobody after that is contacted.')) return;

                var runId = activeRun;
                $stop.disabled = true;
                fetch(STOP_URL.replace('__RUN__', encodeURIComponent(runId)), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                })
                .then(function () { $stop.disabled = false; })
                .catch(function () { $stop.disabled = false; });
            });

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
                startRun();
            });

            loadClients();

            // Reattach to whatever this store already has going, so a reopened page shows the
            // live run instead of an idle composer over the top of one.
            if (RUNNING) {
                $results.style.display = 'none';
                watch(RUNNING);
            }
        })();
    </script>
