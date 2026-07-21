@extends('layouts.vendor.app')

@section('title', 'Connect WhatsApp')

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-chat"></i> Connect WhatsApp</h1>
        </div>

        <div class="row">
            <div class="{{ $connected ? 'col-lg-4' : 'col-lg-7' }}">
                <div class="card">
                    <div class="card-body">
                        @if ($connected)
                            <div class="alert alert-success">
                                <b>✓ Connected.</b> Your WhatsApp number is linked and messages will send from your own number.
                            </div>
                            <table class="table table-sm">
                                <tr><td class="text-muted">Phone Number ID</td><td><b>{{ $store->wa_phone_number_id }}</b></td></tr>
                                <tr><td class="text-muted">Business Account ID</td><td>{{ $store->wa_business_account_id }}</td></tr>
                            </table>
                            <a href="{{ route('vendor.whatsapp.templates') }}" class="btn btn-outline-primary btn-sm mb-2"><i class="tio-receipt"></i> Manage Message Templates</a>
                            <form method="post" action="{{ route('vendor.whatsapp.disconnect') }}" onsubmit="return confirm('Disconnect WhatsApp? Messages will fall back to the platform number.')">
                                @csrf
                                <button class="btn btn-outline-danger btn-sm">Disconnect</button>
                            </form>
                        @else
                            <p>Connect your business WhatsApp number to send invoices, reminders and updates to your customers directly from your own number.</p>
                            @if (!$es['ready'])
                                <div class="alert alert-warning">WhatsApp onboarding isn’t available yet. Please contact support.</div>
                            @else
                                <button id="wa-connect-btn" class="btn btn-success btn-lg">
                                    <i class="tio-chat"></i> Connect WhatsApp
                                </button>
                                <div id="wa-status" class="mt-3 text-muted" style="display:none;"></div>
                            @endif
                            <hr>
                            <small class="text-muted d-block">
                                A secure Facebook window will guide you through connecting your number. You’ll need:
                                a phone number not currently active on the WhatsApp app, and access to receive its verification code.
                            </small>
                        @endif

                        {{-- Sends from the MyChitti platform number, not the vendor's own, so it
                             works before they connect and answers "can MyChitti reach me?". --}}
                        <hr>
                        <h6 class="mb-1" style="font-size:14px;">Test WhatsApp delivery</h6>
                        @php
                            $storePhone = preg_replace('/[^0-9]/', '', (string) ($store->phone ?? ''));
                            $storePhoneMasked = strlen($storePhone) >= 4 ? '******' . substr($storePhone, -4) : null;
                        @endphp
                        @if ($storePhoneMasked)
                            <p class="text-muted mb-2" style="font-size:13px;">
                                We’ll send a test message from MyChitti to your registered number
                                <b>{{ $storePhoneMasked }}</b>. Use this to confirm you can receive
                                WhatsApp alerts such as new-lead notifications.
                            </p>
                            <form method="post" action="{{ route('vendor.whatsapp.test-message') }}">
                                @csrf
                                <button class="btn btn-outline-success btn-sm">
                                    <i class="tio-send"></i> Send Test Message
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning mb-0" style="font-size:13px;">
                                Add a phone number to your store profile to send yourself a test message.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if ($connected)
                <div class="col-lg-8">
                    <div class="card">
                        {{-- Audience counts live in the header, not the picker below: the picker is
                             hidden until an approved template exists, and the vendor still needs to
                             see who they could reach while deciding whether to make one. --}}
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                            <h5 class="card-title mb-0"><i class="tio-send"></i> Bulk Message</h5>
                            <div class="d-flex flex-wrap" style="gap:6px;">
                                <span class="badge badge-soft-info">
                                    {{ $clientCount }} {{ $clientCount == 1 ? 'client' : 'clients' }}
                                </span>
                                <span class="badge badge-soft-primary">
                                    {{ $platformUserCount }} MyChitti {{ $platformUserCount == 1 ? 'user' : 'users' }} in your zone
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            @if ($templateError)
                                <div class="alert alert-warning" style="font-size:13px;">
                                    Couldn’t load your templates: {{ $templateError }}
                                </div>
                            @endif

                            @if (empty($templates))
                                <p class="text-muted mb-2">
                                    You could reach <b>{{ $clientCount + $platformUserCount }}</b> people —
                                    {{ $clientCount }} of your own {{ $clientCount == 1 ? 'client' : 'clients' }}
                                    and {{ $platformUserCount }} MyChitti {{ $platformUserCount == 1 ? 'user' : 'users' }}
                                    in your zone — but you have no approved message templates yet. WhatsApp only allows
                                    business-initiated messages using a template Meta has approved.
                                </p>
                                <a href="{{ route('vendor.whatsapp.templates') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-receipt"></i> Create a Template
                                </a>
                            @else
                                <div class="form-group">
                                    <label class="font-weight-bold" style="font-size:13px;">Template</label>
                                    <select id="wb-template" class="form-control">
                                        <option value="">— Select an approved template —</option>
                                        @foreach ($templates as $i => $t)
                                            <option value="{{ $i }}" @if ($t['unsupported']) disabled @endif>
                                                {{ $t['name'] }} ({{ $t['language'] }})@if ($t['unsupported']) — not supported here, {{ $t['unsupported'] }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="wb-preview" class="border rounded p-3 mb-3 bg-light" style="display:none;">
                                    <div class="text-muted mb-1" style="font-size:11px;text-transform:uppercase;letter-spacing:.4px;">Message preview</div>
                                    <div id="wb-preview-body" style="font-size:13px;white-space:pre-wrap;"></div>
                                </div>

                                <div id="wb-vars" class="mb-3"></div>

                                <div class="form-group">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="font-weight-bold mb-0" style="font-size:13px;">Recipients</label>
                                        <span id="wb-selected-count" class="badge badge-soft-secondary">0 selected</span>
                                    </div>

                                    <ul class="nav nav-pills mb-3" style="gap:6px;">
                                        <li class="nav-item">
                                            <a href="javascript:;" class="nav-link active wb-mode" data-mode="clients" style="font-size:13px;padding:6px 14px;">
                                                My clients <span class="badge badge-soft-light ml-1">{{ $clientCount }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="javascript:;" class="nav-link wb-mode" data-mode="platform" style="font-size:13px;padding:6px 14px;">
                                                MyChitti users <span class="badge badge-soft-light ml-1">{{ $platformUserCount }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="javascript:;" class="nav-link wb-mode" data-mode="nearby" style="font-size:13px;padding:6px 14px;">
                                                Opted in to offers <span class="badge badge-soft-light ml-1">{{ $nearbyUserCount }}</span>
                                            </a>
                                        </li>
                                    </ul>

                                    <div id="wb-pane-clients">
                                        <div class="d-flex mb-2" style="gap:8px;">
                                            <input id="wb-search" type="text" class="form-control form-control-sm"
                                                   placeholder="Search clients by name or phone…">
                                            <button id="wb-select-all" type="button" class="btn btn-sm btn-outline-secondary text-nowrap">Select all</button>
                                            <button id="wb-clear" type="button" class="btn btn-sm btn-outline-secondary text-nowrap">Clear</button>
                                        </div>
                                        <div id="wb-clients" class="border rounded" style="max-height:260px;overflow-y:auto;">
                                            <div class="text-muted text-center p-3" style="font-size:13px;">Loading clients…</div>
                                        </div>
                                        <small id="wb-truncated" class="text-muted" style="display:none;"></small>
                                    </div>

                                    <div id="wb-pane-nearby" style="display:none;">
                                        <div class="border rounded p-3">
                                            <p class="text-muted mb-2" style="font-size:13px;">
                                                People in your zone who ticked <b>“offers from businesses near me”</b>.
                                                They have no history with you — they asked to hear from local businesses.
                                                Numbers stay private and are never shown to you.
                                            </p>
                                            @if ($nearbyUserCount == 0)
                                                <div class="alert alert-info mb-0" style="font-size:13px;">
                                                    Nobody in your zone has opted in yet. This pool fills as customers
                                                    tick the box at signup or in their account settings.
                                                </div>
                                            @else
                                                <label style="font-size:12px;" class="mb-1">How many to message</label>
                                                <input id="wb-nearby-count" type="number" class="form-control form-control-sm"
                                                       style="max-width:200px;" min="1" max="{{ $nearbyUserCount }}"
                                                       value="{{ min(50, $nearbyUserCount) }}">
                                                <small class="text-muted d-block mt-1">
                                                    Maximum {{ $nearbyUserCount }} available now. Anyone who has already
                                                    received {{ \App\Http\Controllers\Vendor\WhatsAppController::NEARBY_MONTHLY_CAP }}
                                                    offers from any business this month is excluded automatically, so the
                                                    number moves as other vendors send.
                                                </small>
                                            @endif
                                        </div>
                                    </div>

                                    <div id="wb-pane-platform" style="display:none;">
                                        <div class="border rounded p-3">
                                            <p class="text-muted mb-2" style="font-size:13px;">
                                                People who have requested services in your zone on MyChitti. You choose
                                                how many to reach — their phone numbers stay private and are never
                                                shown to you.
                                            </p>
                                            @if ($platformUserCount == 0)
                                                <div class="alert alert-info mb-0" style="font-size:13px;">
                                                    No MyChitti users have requested services in your zone yet, so there
                                                    is nobody to reach here right now. This grows as customers in your
                                                    area start using MyChitti — your own clients are unaffected.
                                                </div>
                                            @else
                                                <label style="font-size:12px;" class="mb-1">How many users to message</label>
                                                <input id="wb-platform-count" type="number" class="form-control form-control-sm"
                                                       style="max-width:200px;" min="1" max="{{ $platformUserCount }}"
                                                       value="{{ min(50, $platformUserCount) }}">
                                                <small class="text-muted d-block mt-1">Maximum {{ $platformUserCount }} in your zone.</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <small class="text-muted d-block mb-3">
                                    Anyone who replies <b>STOP</b> is removed automatically and excluded from every
                                    future send.@if (($optOutCount ?? 0) > 0) {{ $optOutCount }} {{ $optOutCount == 1 ? 'person has' : 'people have' }} opted out and {{ $optOutCount == 1 ? 'is' : 'are' }} already excluded from the counts above.@endif
                                    Keeping unwanted messages down protects your number's WhatsApp quality rating.
                                </small>

                                <div class="d-flex align-items-center" style="gap:12px;">
                                    <button id="wb-send" class="btn btn--primary" disabled>Send</button>
                                    <div id="wb-progress" class="flex-grow-1" style="display:none;">
                                        <div class="progress" style="height:6px;">
                                            <div id="wb-progress-bar" class="progress-bar bg-success" style="width:0%;"></div>
                                        </div>
                                        <small id="wb-progress-text" class="text-muted"></small>
                                    </div>
                                </div>

                                <div id="wb-results" class="mt-3" style="display:none;"></div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@if ($connected && !empty($templates))
@push('script_2')
    <script>
        (function () {
            var TEMPLATES = @json($templates);
            var BATCH = {{ \App\Http\Controllers\Vendor\WhatsAppController::BULK_BATCH_LIMIT }};
            var RECIPIENTS_URL = '{{ route('vendor.whatsapp.bulk.recipients') }}';
            var SEND_URL = '{{ route('vendor.whatsapp.bulk.send') }}';
            var CSRF = '{{ csrf_token() }}';

            var PLATFORM_MAX = {{ $platformUserCount }};
            var NEARBY_MAX = {{ $nearbyUserCount }};

            var selected = new Set();
            var loaded = [];
            var searchTimer = null;
            var mode = 'clients';

            // Built by concatenation so Blade never sees a literal double-brace in this script.
            var OPEN = '{' + '{', CLOSE = '}' + '}';
            function token(n) { return OPEN + n + CLOSE; }

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

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            function currentTemplate() {
                return $tpl.value === '' ? null : TEMPLATES[parseInt($tpl.value, 10)];
            }

            function paramValues() {
                return Array.prototype.map.call($vars.querySelectorAll('.wb-var'), function (i) { return i.value; });
            }

            function renderPreview() {
                var t = currentTemplate();
                if (!t) { $preview.style.display = 'none'; return; }
                var body = t.body;
                paramValues().forEach(function (v, i) {
                    body = body.split(token(i + 1)).join(v || token(i + 1));
                });
                $previewBody.innerHTML = esc(body).replace(/\{name\}/g, '<b>[client name]</b>');
                $preview.style.display = 'block';
            }

            function renderVars() {
                var t = currentTemplate();
                $vars.innerHTML = '';
                if (!t || !t.var_count) { renderPreview(); syncSend(); return; }

                var help = document.createElement('small');
                help.className = 'text-muted d-block mb-2';
                help.innerHTML = 'Fill each variable. Type <code>{name}</code> to insert the client’s name.';
                $vars.appendChild(help);

                for (var i = 1; i <= t.var_count; i++) {
                    var wrap = document.createElement('div');
                    wrap.className = 'form-group mb-2';
                    wrap.innerHTML = '<label style="font-size:12px;" class="mb-1">Variable ' + token(i) + '</label>' +
                        '<input type="text" class="form-control form-control-sm wb-var" placeholder="Value for ' + token(i) + '">';
                    $vars.appendChild(wrap);
                }
                Array.prototype.forEach.call($vars.querySelectorAll('.wb-var'), function (input) {
                    input.addEventListener('input', function () { renderPreview(); syncSend(); });
                });
                renderPreview();
                syncSend();
            }

            function countFrom(inputId, max) {
                var $input = document.getElementById(inputId);
                if (!$input) return 0;
                var n = parseInt($input.value, 10);
                if (isNaN(n) || n < 1) return 0;
                return Math.min(n, max);
            }

            function recipientCount() {
                if (mode === 'platform') return countFrom('wb-platform-count', PLATFORM_MAX);
                if (mode === 'nearby') return countFrom('wb-nearby-count', NEARBY_MAX);
                return selected.size;
            }

            function syncSend() {
                var t = currentTemplate();
                var filled = !t || !t.var_count || paramValues().every(function (v) { return v.trim() !== ''; });
                var n = recipientCount();
                $send.disabled = !t || !filled || n === 0;
                $count.textContent = mode === 'clients'
                    ? selected.size + ' selected'
                    : n + (mode === 'nearby' ? ' opted in' : ' MyChitti user' + (n === 1 ? '' : 's'));
                $send.textContent = n
                    ? 'Send to ' + n + ' recipient' + (n === 1 ? '' : 's')
                    : 'Send';
            }

            function setMode(next) {
                mode = next;
                Array.prototype.forEach.call(document.querySelectorAll('.wb-mode'), function (el) {
                    el.classList.toggle('active', el.dataset.mode === next);
                });
                document.getElementById('wb-pane-clients').style.display = next === 'clients' ? 'block' : 'none';
                document.getElementById('wb-pane-platform').style.display = next === 'platform' ? 'block' : 'none';
                document.getElementById('wb-pane-nearby').style.display = next === 'nearby' ? 'block' : 'none';
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

                if (mode === 'platform' || mode === 'nearby') {
                    // The server walks users ordered by id, so an offset/limit pair addresses
                    // each recipient exactly once without the browser ever seeing a number.
                    for (var o = 0; o < total; o += BATCH) {
                        batches.push({ mode: mode, offset: o, limit: Math.min(BATCH, total - o) });
                    }
                } else {
                    var ids = Array.from(selected);
                    for (var i = 0; i < ids.length; i += BATCH) {
                        batches.push({ mode: 'clients', client_ids: ids.slice(i, i + BATCH) });
                    }
                }

                var done = 0, sent = 0, failures = [];
                $send.disabled = true;
                $progress.style.display = 'block';
                $results.style.display = 'none';

                function batchSize(b) {
                    return b.mode === 'clients' ? b.client_ids.length : b.limit;
                }

                function step(index) {
                    if (index >= batches.length) {
                        $ptext.textContent = 'Finished — ' + sent + ' sent, ' + failures.length + ' failed.';
                        showResults(sent, failures);
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
                            params: paramValues()
                        }, batches[index]))
                    })
                    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                    .then(function (res) {
                        if (!res.ok) {
                            failures.push({ name: '—', phone: '—', error: res.d.message || 'Request rejected.' });
                        } else {
                            sent += res.d.sent || 0;
                            (res.d.results || []).forEach(function (r) { if (!r.success) failures.push(r); });
                        }
                        done += batchSize(batches[index]);
                        var pct = Math.round((done / total) * 100);
                        $bar.style.width = pct + '%';
                        $ptext.textContent = done + ' of ' + total + ' processed…';
                        step(index + 1);
                    })
                    .catch(function () {
                        failures.push({ name: '—', phone: '—', error: 'Network error on a batch of ' + batchSize(batches[index]) + '.' });
                        done += batchSize(batches[index]);
                        step(index + 1);
                    });
                }
                step(0);
            }

            function showResults(sent, failures) {
                var html = '<div class="alert ' + (failures.length ? 'alert-warning' : 'alert-success') + '" style="font-size:13px;">' +
                    '<b>' + sent + '</b> message' + (sent === 1 ? '' : 's') + ' sent' +
                    (failures.length ? ', <b>' + failures.length + '</b> failed.' : '.') + '</div>';

                if (failures.length) {
                    html += '<div class="border rounded" style="max-height:200px;overflow-y:auto;">' +
                        failures.map(function (f) {
                            return '<div class="px-3 py-2 border-bottom" style="font-size:12px;">' +
                                '<b>' + esc(f.name) + '</b> <span class="text-muted">' + esc(f.phone) + '</span><br>' +
                                '<span class="text-danger">' + esc(f.error) + '</span></div>';
                        }).join('') + '</div>';
                }
                $results.innerHTML = html;
                $results.style.display = 'block';
            }

            $tpl.addEventListener('change', renderVars);
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
            ['wb-platform-count', 'wb-nearby-count'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.addEventListener('input', syncSend);
            });
            $send.addEventListener('click', function () {
                var n = recipientCount();
                var who = mode === 'clients'
                    ? 'client(s)'
                    : (mode === 'nearby'
                        ? 'people who opted in to offers from nearby businesses'
                        : 'MyChitti user(s) in your zone');
                if (!confirm('Send this template to ' + n + ' ' + who + '?')) return;
                sendBatches();
            });

            loadClients();
        })();
    </script>
@endpush
@endif

@if (!$connected && $es['ready'])
@push('script_2')
    <script>
        window.fbAsyncInit = function () {
            FB.init({
                appId: '{{ $es['app_id'] }}',
                autoLogAppEvents: true,
                xfbml: true,
                version: '{{ $es['api_version'] }}'
            });
        };
    </script>
    <script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>
    <script>
        var WA_SESSION = { phone_number_id: null, waba_id: null };

        // Embedded Signup posts the selected WABA + phone number id via window.postMessage.
        window.addEventListener('message', function (event) {
            if (event.origin !== 'https://www.facebook.com' && event.origin !== 'https://web.facebook.com') return;
            try {
                var data = JSON.parse(event.data);
                if (data.type === 'WA_EMBEDDED_SIGNUP' && data.event === 'FINISH') {
                    WA_SESSION.phone_number_id = data.data.phone_number_id;
                    WA_SESSION.waba_id = data.data.waba_id;
                }
            } catch (e) { /* not our message */ }
        });

        function waStatus(msg, kind) {
            var el = document.getElementById('wa-status');
            el.style.display = 'block';
            el.className = 'mt-3 ' + (kind === 'error' ? 'text-danger' : (kind === 'ok' ? 'text-success' : 'text-muted'));
            el.textContent = msg;
        }

        document.getElementById('wa-connect-btn').addEventListener('click', function () {
            if (typeof FB === 'undefined') { waStatus('Facebook SDK not loaded yet, please retry.', 'error'); return; }
            waStatus('Opening WhatsApp signup…');
            FB.login(function (response) {
                var code = response && response.authResponse && response.authResponse.code;
                if (!code) { waStatus('Signup cancelled or no code returned.', 'error'); return; }
                if (!WA_SESSION.phone_number_id || !WA_SESSION.waba_id) {
                    waStatus('Could not read the selected number. Please retry and complete all steps.', 'error');
                    return;
                }
                waStatus('Finalising connection…');
                fetch('{{ route('vendor.whatsapp.connect.finish') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        code: code,
                        phone_number_id: WA_SESSION.phone_number_id,
                        waba_id: WA_SESSION.waba_id
                    })
                })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.success) { waStatus('Connected! Reloading…', 'ok'); setTimeout(function () { location.reload(); }, 1200); }
                    else { waStatus(d.message || 'Connection failed.', 'error'); }
                })
                .catch(function () { waStatus('Network error while connecting.', 'error'); });
            }, {
                config_id: '{{ $es['config_id'] }}',
                response_type: 'code',
                override_default_response_type: true,
                extras: { setup: {}, sessionInfoVersion: '3' }
            });
        });
    </script>
@endpush
@endif
