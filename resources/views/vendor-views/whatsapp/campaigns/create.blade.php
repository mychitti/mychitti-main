@extends('layouts.vendor.app')

@section('title', 'New WhatsApp Campaign')

@push('css_or_js')
    @include('vendor-views.whatsapp.partials._ui')
    <style>
        .cs-step { border:1px solid var(--wa-line); border-radius:12px; padding:14px; margin-bottom:12px; background:#fff; }
        .cs-step-h { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; }
        .cs-step-no { font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:var(--wa-mute); font-weight:700; }
        .cs-preview { background:var(--wa-bg); border-radius:10px; padding:10px 12px; font-size:13px; white-space:pre-wrap; }
        .cs-btn-chip { display:inline-block; font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px;
            background:#e8f7ef; color:var(--wa-green-d); margin:2px 4px 2px 0; }
        .cs-client-row { display:flex; align-items:center; gap:10px; padding:8px 12px; border-bottom:1px solid #f6f7fa; font-size:13px; }
        .cs-client-row:last-child { border-bottom:0; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
            <div>
                <h1 class="page-header-title mb-0"><i class="tio-send"></i> New Campaign Series</h1>
                <span class="wa-sub">One audience, a run of templates, and replies that decide who hears from you next.</span>
            </div>
            <a href="{{ route('vendor.whatsapp.campaigns') }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-back-ui"></i> All campaigns
            </a>
        </div>

        @if (!$connected)
            <div class="wa-card">
                <div class="wa-empty">
                    <i class="tio-send-outlined"></i>
                    <div class="wa-empty-t">Connect your WhatsApp number first</div>
                    @if (hasAnyModulePermission(['whatsapp_connection']))
                        <a href="{{ route('vendor.whatsapp.connect') }}" class="btn btn-sm btn--primary mt-2">Connect WhatsApp</a>
                    @endif
                </div>
            </div>
        @elseif (empty($templates))
            <div class="wa-card">
                <div class="wa-empty">
                    <i class="tio-receipt-outlined"></i>
                    <div class="wa-empty-t">You have no templates to send</div>
                    <div class="wa-empty-s mb-3">
                        A series needs at least one approved template. Give step 1 two quick-reply buttons —
                        “Interested” and “Not interested” — so the follow-ups have something to work with.
                    </div>
                    @if (hasPermission('whatsapp_templates', 'add'))
                        <a href="{{ route('vendor.whatsapp.templates') }}" class="btn btn-sm btn--primary">Create a template</a>
                    @endif
                </div>
            </div>
        @else
            @if ($templateError)
                <div class="alert alert-warning" style="font-size:13px;">Couldn’t load all your templates: {{ $templateError }}</div>
            @endif
            @if (!$active)
                <div class="alert alert-warning" style="font-size:13px;">
                    Your subscription isn’t active — you can save this campaign, but it won’t send until you
                    @if (hasAnyModulePermission(['whatsapp_billing']))<a href="{{ route('vendor.whatsapp.billing') }}">activate your plan</a>.@else ask the owner to activate your plan.@endif
                </div>
            @endif

            <form action="{{ route('vendor.whatsapp.campaigns.store') }}" method="post" id="cs-form">
                @csrf
                <div class="row">
                    <div class="col-lg-8">
                        <div class="wa-card mb-3">
                            <div class="wa-card-h">1. Campaign &amp; audience</div>
                            <div class="wa-card-b">
                                <div class="form-group">
                                    <label class="font-weight-bold" style="font-size:13px;">Campaign name</label>
                                    <input type="text" name="name" class="form-control" maxlength="150" required
                                           placeholder="e.g. Diwali offer — September" value="{{ old('name') }}">
                                    <small class="text-muted">Only you see this.</small>
                                </div>

                                <input type="hidden" name="audience" id="cs-audience" value="{{ old('audience', 'clients') }}">
                                <label class="font-weight-bold d-block" style="font-size:13px;">Who receives it</label>
                                <ul class="nav nav-pills mb-3" style="gap:6px;">
                                    <li class="nav-item">
                                        <a href="javascript:;" class="nav-link active cs-aud" data-aud="clients" style="font-size:13px;padding:6px 14px;">
                                            My customers <span class="badge badge-soft-light ml-1">{{ $clientCount }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="javascript:;" class="nav-link cs-aud" data-aud="platform" style="font-size:13px;padding:6px 14px;">
                                            MyChitti customers <span class="badge badge-soft-light ml-1">{{ $platformUserCount }}</span>
                                        </a>
                                    </li>
                                </ul>

                                <input type="hidden" name="recipient_mode" id="cs-mode" value="all">

                                <div id="cs-pane-clients">
                                    <div class="d-flex align-items-center flex-wrap mb-2" style="gap:8px;">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="cs-all" name="cs_pick" class="custom-control-input" value="all" checked>
                                            <label class="custom-control-label" for="cs-all" style="font-size:13px;">
                                                All {{ $clientCount }} of my customers
                                            </label>
                                        </div>
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="cs-some" name="cs_pick" class="custom-control-input" value="selected">
                                            <label class="custom-control-label" for="cs-some" style="font-size:13px;">Pick people</label>
                                        </div>
                                    </div>

                                    <div id="cs-picker" style="display:none;">
                                        <div class="d-flex mb-2" style="gap:8px;">
                                            <input id="cs-search" type="text" class="form-control form-control-sm"
                                                   placeholder="Search by name or phone…">
                                            <button id="cs-select-all" type="button" class="btn btn-sm btn-outline-secondary text-nowrap">Select all</button>
                                            <button id="cs-clear" type="button" class="btn btn-sm btn-outline-secondary text-nowrap">Clear</button>
                                        </div>
                                        <div id="cs-clients" class="border rounded" style="max-height:280px;overflow-y:auto;">
                                            <div class="text-muted text-center p-3" style="font-size:13px;">Loading customers…</div>
                                        </div>
                                        <small class="text-muted d-block mt-1"><span id="cs-selected-count">0</span> selected</small>
                                    </div>
                                </div>

                                <div id="cs-pane-platform" style="display:none;">
                                    @if ($platformUserCount == 0)
                                        <div class="alert alert-info mb-0" style="font-size:13px;">
                                            No MyChitti customers match your city yet, so there is nobody to reach here
                                            right now. Your own customer list is unaffected.
                                        </div>
                                    @else
                                        <div class="wa-note mb-2">
                                            MyChitti customers in your city. Their numbers stay private — you’ll see the
                                            replies and the counts, never the phone numbers.
                                        </div>
                                        <label style="font-size:12px;" class="mb-1">How many to include</label>
                                        <input type="number" name="recipient_limit" id="cs-limit" class="form-control form-control-sm"
                                               style="max-width:200px;" min="1" max="{{ min($platformUserCount, $maxRecipients) }}"
                                               value="{{ min(100, $platformUserCount) }}">
                                        <small class="text-muted d-block mt-1">
                                            Up to {{ min($platformUserCount, $maxRecipients) }} available now. Anyone who
                                            already hit this month’s offer cap from any business is left out automatically.
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="wa-card mb-3">
                            <div class="wa-card-h">
                                2. The series
                                <span class="wa-sub">Up to {{ $maxSteps }} templates</span>
                            </div>
                            <div class="wa-card-b">
                                <div class="wa-note mb-3">
                                    Step 1 goes out as soon as you start the campaign. Every later step waits its delay,
                                    counted from the moment the previous step finished sending — and by default it skips
                                    anyone who tapped <b>Not interested</b>, while still reaching the people who never
                                    answered.
                                </div>

                                <div id="cs-steps"></div>

                                <button type="button" id="cs-add-step" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-add"></i> Add another template
                                </button>
                            </div>
                        </div>

                        <div class="wa-card mb-3">
                            <div class="wa-card-h">3. Reading the replies</div>
                            <div class="wa-card-b">
                                <div class="wa-row">
                                    <span><b>Button taps</b><div class="wa-sub">Interested / Not interested</div></span>
                                    <span class="wa-chip badge-soft-success">Read exactly</span>
                                </div>
                                <div class="wa-row">
                                    <span>
                                        <b>Typed replies</b>
                                        <div class="wa-sub">
                                            @if ($aiReading)
                                                “nahi chahiye”, “price kitna hai”, “maybe next month”
                                            @else
                                                Matched against your word lists
                                            @endif
                                        </div>
                                    </span>
                                    @if ($aiReading)
                                        <span class="wa-chip badge-soft-info">Read by AI</span>
                                    @else
                                        <span class="wa-chip badge-soft-secondary">Word matching</span>
                                    @endif
                                </div>

                                @if ($aiReading)
                                    <div class="wa-note mt-3">
                                        Your AI Agent plan reads anything typed, in any language, and understands
                                        “not now” or a question about price. Button taps never use AI — the label is
                                        already exact, so those cost you no tokens. If your tokens run out, typed
                                        replies fall back to the word lists rather than going unread.
                                    </div>
                                @else
                                    <div class="wa-note mt-3">
                                        Typed replies are matched against the word lists below. They can’t catch
                                        “nahi chahiye” or “maybe next month” — an
                                        @if (hasAnyModulePermission(['whatsapp_billing']))<a href="{{ route('vendor.whatsapp.billing') }}">AI Agent plan</a>@else AI Agent plan @endif reads those
                                        properly, in any language. Button taps work perfectly either way.
                                    </div>
                                @endif

                                <button type="button" class="btn btn-sm btn-link px-0 mt-2" data-toggle="collapse"
                                        data-target="#cs-labels" style="font-size:12px;">
                                    Advanced — edit the word lists
                                </button>
                                <div class="collapse" id="cs-labels">
                                    <p class="wa-sub mb-3">
                                        Filled in from your template’s buttons automatically. Comma separated, matched
                                        case-insensitively; “not interested” is checked before “interested”, so the two
                                        never collide.
                                    </p>
                                    <div class="form-group">
                                        <label class="font-weight-bold" style="font-size:13px;">Counts as interested</label>
                                        <input type="text" name="positive_labels" id="cs-positive" class="form-control"
                                               value="{{ old('positive_labels', $defaultPositive) }}">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold" style="font-size:13px;">Removes them from the series</label>
                                        <input type="text" name="negative_labels" id="cs-negative" class="form-control"
                                               value="{{ old('negative_labels', $defaultNegative) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="wa-card mb-3">
                            <div class="wa-card-h">Summary</div>
                            <div class="wa-card-b">
                                <div class="wa-row">
                                    <span>Audience</span>
                                    <b id="cs-sum-aud">My customers</b>
                                </div>
                                <div class="wa-row">
                                    <span>Recipients</span>
                                    <b id="cs-sum-count">{{ $clientCount }}</b>
                                </div>
                                <div class="wa-row">
                                    <span>Templates in series</span>
                                    <b id="cs-sum-steps">1</b>
                                </div>
                                <div class="wa-row">
                                    <span>Per message</span>
                                    <b id="cs-sum-rate">{{ _price($ownRate) }}</b>
                                </div>
                                <div class="wa-row">
                                    <span>Worst-case cost</span>
                                    <b id="cs-sum-cost">—</b>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    Worst case assumes nobody drops out. Every “Not interested” makes the series cheaper,
                                    and charges leave your wallet as each message goes out.
                                </small>
                                <button type="submit" class="btn btn--primary btn-block mt-3">Save campaign</button>
                                <small class="text-muted d-block mt-2">
                                    Saving doesn’t send anything — you review it, then press Start.
                                </small>
                            </div>
                        </div>

                        <div class="wa-card">
                            <div class="wa-card-h">Getting this right</div>
                            <div class="wa-card-b">
                                <ul class="pl-3 mb-0 wa-sub" style="line-height:1.7;">
                                    <li>Give step 1’s template two quick-reply buttons so answering is one tap.</li>
                                    <li>Leave at least a day between steps — three messages in an hour gets you blocked.</li>
                                    <li>Anyone replying <b>STOP</b> leaves the series and every future send automatically.</li>
                                    <li>The audience is frozen when you save, so customers added later aren’t pulled in mid-series.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>
@endsection

@if ($connected && !empty($templates))
@push('script_2')
    <script>
        (function () {
            var TEMPLATES = @json($templates);
            var TARGETS = @json($targets);
            var RECIPIENTS_URL = '{{ route('vendor.whatsapp.bulk.recipients') }}';
            var CLIENT_COUNT = {{ $clientCount }};
            var PLATFORM_COUNT = {{ $platformUserCount }};
            var MAX_STEPS = {{ $maxSteps }};
            var RATES = { clients: {{ $ownRate }}, platform: {{ $platformRate }} };
            var CURRENCY = '{{ \App\CentralLogics\Helpers::currency_symbol() }}';

            var stepIndex = 0;

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            function templateOptions() {
                var html = '<option value="">— Select a template —</option>';
                TEMPLATES.forEach(function (t, i) {
                    var note = '';
                    if ((t.status || 'APPROVED') !== 'APPROVED') {
                        note = ' — ' + t.status + ', Meta will reject the send';
                    } else if (t.unsupported) {
                        note = ' — not supported here, ' + t.unsupported;
                    }
                    html += '<option value="' + i + '"' + (t.unsupported ? ' disabled' : '') + '>'
                        + esc(t.name) + ' (' + esc(t.language) + ')' + esc(note) + '</option>';
                });
                return html;
            }

            function targetOptions(selected) {
                var html = '';
                Object.keys(TARGETS).forEach(function (key) {
                    html += '<option value="' + key + '"' + (key === selected ? ' selected' : '') + '>'
                        + esc(TARGETS[key]) + '</option>';
                });
                return html;
            }

            // One step block. Step 1 has no delay and no reply filter to apply — nobody has
            // answered anything yet — so those controls are hidden rather than shown as no-ops.
            function addStep() {
                var $steps = $('#cs-steps');
                if ($steps.children('.cs-step').length >= MAX_STEPS) { return; }

                var i = stepIndex++;
                var first = $steps.children('.cs-step').length === 0;

                var html = '<div class="cs-step" data-index="' + i + '">'
                    + '<div class="cs-step-h">'
                    + '<span class="cs-step-no">Step <span class="cs-step-label">' + ($steps.children('.cs-step').length + 1) + '</span>'
                    + (first ? ' · the opener' : '') + '</span>'
                    + (first ? '' : '<button type="button" class="btn btn-sm btn-outline-danger cs-remove"><i class="tio-clear"></i></button>')
                    + '</div>'
                    + '<div class="form-group mb-2">'
                    + '<label style="font-size:12px;" class="mb-1">Template</label>'
                    + '<select class="form-control form-control-sm cs-tpl">' + templateOptions() + '</select>'
                    + '<input type="hidden" name="steps[' + i + '][template]" class="cs-tpl-name">'
                    + '<input type="hidden" name="steps[' + i + '][language]" class="cs-tpl-lang">'
                    + '</div>'
                    + '<div class="cs-buttons mb-2"></div>'
                    + '<div class="cs-vars"></div>'
                    + '<div class="cs-preview mb-2" style="display:none;"></div>';

                if (first) {
                    // The opener always reaches the whole audience the moment the campaign starts,
                    // so it gets hidden fields rather than disabled controls — a second pair of
                    // inputs with the same names would fight the visible ones on submit.
                    html += '<input type="hidden" name="steps[' + i + '][target]" value="all">'
                        + '<input type="hidden" name="steps[' + i + '][delay_hours]" value="0">'
                        + '<div class="wa-sub">Goes to the whole audience as soon as you start the campaign.</div>';
                } else {
                    html += '<div class="row" style="row-gap:8px;">'
                        + '<div class="col-sm-7">'
                        + '<label style="font-size:12px;" class="mb-1">Send to</label>'
                        + '<select class="form-control form-control-sm" name="steps[' + i + '][target]">'
                        + targetOptions('interested_no_reply') + '</select>'
                        + '</div>'
                        + '<div class="col-sm-5">'
                        + '<label style="font-size:12px;" class="mb-1">Wait before sending</label>'
                        + '<div class="input-group input-group-sm">'
                        + '<input type="number" class="form-control" name="steps[' + i + '][delay_hours]" min="0" max="2160" value="48">'
                        + '<div class="input-group-append"><span class="input-group-text">hours</span></div>'
                        + '</div>'
                        + '</div>'
                        + '</div>';
                }

                $steps.append(html + '</div>');
                renumber();
            }

            function renumber() {
                $('#cs-steps').children('.cs-step').each(function (n) {
                    $(this).find('.cs-step-label').first().text(n + 1);
                });
                $('#cs-sum-steps').text($('#cs-steps').children('.cs-step').length);
                $('#cs-add-step').prop('disabled', $('#cs-steps').children('.cs-step').length >= MAX_STEPS);
                updateCost();
            }

            // Variable slots for the chosen template. Slots the platform fills per recipient are
            // shown as filled rather than asked for, so the vendor can't overwrite a customer's
            // own name with a literal.
            function renderStep($step) {
                var idx = $step.data('index');
                var t = TEMPLATES[$step.find('.cs-tpl').val()];
                var $vars = $step.find('.cs-vars').empty();
                var $buttons = $step.find('.cs-buttons').empty();
                var $preview = $step.find('.cs-preview');

                if (!t) {
                    $step.find('.cs-tpl-name, .cs-tpl-lang').val('');
                    $preview.hide();
                    return;
                }

                $step.find('.cs-tpl-name').val(t.name);
                $step.find('.cs-tpl-lang').val(t.language);

                (t.vars || []).forEach(function (v, n) {
                    var name = 'steps[' + idx + '][params][' + n + ']';
                    if (v.auto) {
                        $vars.append('<div class="form-group mb-2">'
                            + '<label style="font-size:12px;" class="mb-1">' + esc(v.label) + '</label>'
                            + '<input type="text" class="form-control form-control-sm" value="Filled in per recipient" disabled>'
                            + '<input type="hidden" name="' + name + '[key]" value="' + esc(v.key) + '">'
                            + '<input type="hidden" name="' + name + '[value]" value="">'
                            + '</div>');
                    } else {
                        $vars.append('<div class="form-group mb-2">'
                            + '<label style="font-size:12px;" class="mb-1">' + esc(v.label) + '</label>'
                            + '<input type="hidden" name="' + name + '[key]" value="' + esc(v.key) + '">'
                            + '<input type="text" class="form-control form-control-sm cs-var" name="' + name + '[value]"'
                            + ' placeholder="Value — {name} becomes the customer\'s name" required>'
                            + '</div>');
                    }
                });

                if ((t.buttons || []).length) {
                    var chips = t.buttons.map(function (b) { return '<span class="cs-btn-chip">' + esc(b) + '</span>'; }).join('');
                    $buttons.html('<div class="wa-sub mb-1">Buttons on this template</div>' + chips);
                    seedLabels(t.buttons);
                } else {
                    $buttons.html('<div class="wa-sub text-warning">'
                        + 'This template has no quick-reply buttons, so people can only answer by typing. '
                        + 'Add buttons to the template for one-tap answers.</div>');
                }

                if (t.body) {
                    $preview.text(t.body).show();
                } else {
                    $preview.hide();
                }
            }

            // Match the reply lists to the actual button labels the first time we see them — a
            // template whose button says "Yes please" would otherwise score as no answer at all.
            function seedLabels(buttons) {
                var negative = $('#cs-negative').val().toLowerCase();
                var positive = $('#cs-positive').val().toLowerCase();

                buttons.forEach(function (label) {
                    var clean = String(label).trim();
                    if (!clean) { return; }
                    var lower = clean.toLowerCase();
                    var looksNegative = /\b(no|not|never|stop|later)\b/.test(lower);
                    var list = looksNegative ? negative : positive;
                    if (list.indexOf(lower) !== -1) { return; }

                    if (looksNegative) {
                        negative = negative ? negative + ', ' + lower : lower;
                    } else {
                        positive = positive ? positive + ', ' + lower : lower;
                    }
                });

                $('#cs-positive').val(positive);
                $('#cs-negative').val(negative);
            }

            function audience() { return $('#cs-audience').val(); }

            function recipientCount() {
                if (audience() === 'platform') {
                    return Math.min(parseInt($('#cs-limit').val() || 0, 10) || 0, PLATFORM_COUNT);
                }
                if ($('#cs-mode').val() === 'selected') {
                    return $('#cs-clients input.cs-client:checked').length;
                }
                return CLIENT_COUNT;
            }

            function updateCost() {
                var count = recipientCount();
                var steps = $('#cs-steps').children('.cs-step').length;
                var rate = RATES[audience()] || 0;

                $('#cs-sum-count').text(count);
                $('#cs-sum-rate').text(CURRENCY + rate.toFixed(2));
                $('#cs-sum-cost').text(CURRENCY + (count * steps * rate).toFixed(2));
                $('#cs-sum-aud').text(audience() === 'platform' ? 'MyChitti customers' : 'My customers');
            }

            var clientsLoaded = false;
            function loadClients(search) {
                $.get(RECIPIENTS_URL, { search: search || '' }, function (res) {
                    if (!res.success) { return; }
                    var html = '';
                    (res.clients || []).forEach(function (c) {
                        html += '<label class="cs-client-row mb-0">'
                            + '<input type="checkbox" class="cs-client" name="client_ids[]" value="' + c.id + '">'
                            + '<span class="flex-grow-1">' + esc(c.f_name || 'Customer') + '</span>'
                            + '<span class="text-muted">' + esc(c.phone || '') + '</span>'
                            + '</label>';
                    });
                    if (!html) {
                        html = '<div class="text-muted text-center p-3" style="font-size:13px;">No customers match.</div>';
                    }
                    $('#cs-clients').html(html);
                    if (res.truncated) {
                        $('#cs-clients').append('<div class="text-muted p-2" style="font-size:12px;">'
                            + 'Showing the first ' + (res.clients || []).length + ' of ' + res.total + ' — search to narrow it down.</div>');
                    }
                    clientsLoaded = true;
                    countSelected();
                });
            }

            function countSelected() {
                $('#cs-selected-count').text($('#cs-clients input.cs-client:checked').length);
                updateCost();
            }

            $('#cs-add-step').on('click', addStep);

            $(document).on('change', '.cs-tpl', function () {
                renderStep($(this).closest('.cs-step'));
            });

            $(document).on('click', '.cs-remove', function () {
                $(this).closest('.cs-step').remove();
                renumber();
            });

            $('.cs-aud').on('click', function () {
                var aud = $(this).data('aud');
                $('.cs-aud').removeClass('active');
                $(this).addClass('active');
                $('#cs-audience').val(aud);
                $('#cs-pane-clients').toggle(aud === 'clients');
                $('#cs-pane-platform').toggle(aud === 'platform');
                $('#cs-mode').val(aud === 'platform' ? 'limit' : $('input[name=cs_pick]:checked').val());
                updateCost();
            });

            $('input[name=cs_pick]').on('change', function () {
                var pick = $(this).val();
                $('#cs-mode').val(pick);
                $('#cs-picker').toggle(pick === 'selected');
                if (pick === 'selected' && !clientsLoaded) { loadClients(''); }
                updateCost();
            });

            $('#cs-search').on('input', function () {
                var term = $(this).val();
                clearTimeout(window.csSearchTimer);
                window.csSearchTimer = setTimeout(function () { loadClients(term); }, 300);
            });

            $('#cs-select-all').on('click', function () {
                $('#cs-clients input.cs-client').prop('checked', true);
                countSelected();
            });
            $('#cs-clear').on('click', function () {
                $('#cs-clients input.cs-client').prop('checked', false);
                countSelected();
            });
            $(document).on('change', 'input.cs-client', countSelected);
            $('#cs-limit').on('input', updateCost);

            $('#cs-form').on('submit', function (e) {
                if (!$('#cs-steps').children('.cs-step').length) {
                    e.preventDefault();
                    alert('Add at least one template to the series.');
                    return;
                }
                var missing = false;
                $('#cs-steps').children('.cs-step').each(function () {
                    if (!$(this).find('.cs-tpl-name').val()) { missing = true; }
                });
                if (missing) {
                    e.preventDefault();
                    alert('Every step needs a template.');
                    return;
                }
                if ($('#cs-mode').val() === 'selected' && !$('#cs-clients input.cs-client:checked').length) {
                    e.preventDefault();
                    alert('Pick at least one customer, or switch to “All my customers”.');
                }
            });

            addStep();
            updateCost();
        })();
    </script>
@endpush
@endif
