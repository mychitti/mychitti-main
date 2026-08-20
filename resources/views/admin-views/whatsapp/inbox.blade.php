@extends('layouts.admin.app')

@section('title', translate('MyChitti WhatsApp Chats'))

@push('css_or_js')
<style>
    .wchat-wrap { display:flex; height:calc(100vh - 220px); min-height:480px; border:1px solid #e7eaf3; border-radius:10px; overflow:hidden; background:#fff; }
    .wchat-side { width:320px; min-width:260px; border-right:1px solid #e7eaf3; display:flex; flex-direction:column; background:#fff; }
    .wchat-side-head { padding:10px 12px; border-bottom:1px solid #e7eaf3; background:#f0f2f5; }
    .wchat-search { border-radius:18px; font-size:13px; }
    .wchat-threads { flex:1; overflow-y:auto; }
    .wchat-thread { display:flex; gap:10px; padding:10px 12px; cursor:pointer; border-bottom:1px solid #f5f6f8; align-items:center; }
    .wchat-thread:hover { background:#f5f6f6; }
    .wchat-thread.active { background:#e9edef; }
    .wchat-avatar { width:42px; height:42px; border-radius:50%; background:#25D366; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:16px; flex-shrink:0; }
    .wchat-thread-main { min-width:0; flex:1; }
    .wchat-thread-name { font-weight:600; font-size:14px; color:#111b21; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .wchat-thread-last { font-size:12px; color:#667781; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .wchat-thread-time { font-size:11px; color:#667781; flex-shrink:0; align-self:flex-start; margin-top:2px; }
    .wchat-kind { font-size:10px; padding:1px 6px; border-radius:8px; margin-left:4px; }
    .wchat-kind.vendor { background:#e0ecff; color:#1e40af; }
    .wchat-kind.customer { background:#e7f7ec; color:#137a3e; }
    .wchat-wait { font-size:10px; padding:1px 6px; border-radius:8px; margin-left:4px; background:#fdeaea; color:#c0392b; font-weight:700; }
    .wchat-thread.waiting { background:#fffaf0; border-left:3px solid #e8a33d; }
    .wchat-filter { display:flex; gap:6px; margin-top:6px; }
    .wchat-filter button { flex:1; font-size:11.5px; padding:3px 8px; border-radius:999px; border:1px solid #dfe3e8;
                           background:#fff; color:#56606e; cursor:pointer; }
    .wchat-filter button.on { background:#128c7e; border-color:#128c7e; color:#fff; }
    .wchat-filter .cnt { font-weight:700; }
    /* The composer is align-items:flex-end, so the attach button needs its own height to sit
       level with the round send button rather than riding up as the textarea grows. */
    .wchat-attach { background:none; border:none; color:#54656f; font-size:20px; height:44px; width:36px;
                    flex-shrink:0; cursor:pointer; }
    .wchat-attach:hover { color:#128c7e; }
    .wchat-attach:disabled { opacity:.5; cursor:default; }
    .wbubble img.wmedia, .wbubble video.wmedia { max-width:230px; max-height:230px; border-radius:8px; display:block; margin-bottom:4px; }
    .wbubble a.wfile { display:flex; align-items:center; gap:7px; padding:7px 9px; background:rgba(0,0,0,.05);
                       border-radius:8px; margin-bottom:4px; color:inherit; text-decoration:none; font-size:12.5px; }
    .wbubble a.wfile i { font-size:18px; }
    .wchat-pending { display:none; align-items:center; gap:8px; padding:6px 12px; font-size:12px; color:#54656f;
                     background:#fffaf0; border-top:1px solid #f0e4cf; }
    .wchat-main { flex:1; display:flex; flex-direction:column; min-width:0; background:#efeae2; }
    .wchat-head { padding:10px 16px; background:#f0f2f5; border-bottom:1px solid #e7eaf3; display:flex; align-items:center; gap:10px; }
    .wchat-msgs { flex:1; overflow-y:auto; padding:18px 7% 10px;
        background-color:#efeae2;
        background-image:radial-gradient(#d9d3c8 0.6px, transparent 0.6px);
        background-size:22px 22px; }
    .wchat-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#667781; background:#f0f2f5; }
    .wbubble { width:fit-content; min-width:110px; max-width:70%; margin-bottom:6px; padding:6px 9px 7px; border-radius:8px; font-size:13.5px; line-height:1.45;
        box-shadow:0 1px 0.5px rgba(11,20,26,.13); position:relative; white-space:pre-wrap; word-wrap:break-word; }
    .wbubble.in  { background:#fff;    margin-right:auto; border-top-left-radius:0; }
    .wbubble.out { background:#d9fdd3; margin-left:auto;  border-top-right-radius:0; }
    .wbubble .wmeta { font-size:10.5px; color:#667781; text-align:right; margin-top:2px; user-select:none; }
    .wbubble .wticks { letter-spacing:-2px; margin-left:3px; }
    .wbubble .wticks.read { color:#53bdeb; }
    .wbubble .wfail { color:#dc3545; font-size:11px; display:block; text-align:right; }
    .wchat-day { text-align:center; margin:10px 0; }
    .wchat-day span { background:#fff; color:#54656f; font-size:11px; padding:4px 10px; border-radius:8px; box-shadow:0 1px 0.5px rgba(11,20,26,.13); }
    .wchat-window-note { background:#fff8e1; color:#7a6a1f; font-size:12px; padding:6px 14px; border-top:1px solid #f0e6bb; }
    .wchat-input { display:flex; gap:8px; padding:10px 14px; background:#f0f2f5; align-items:flex-end; }
    .wchat-input textarea { flex:1; resize:none; border-radius:10px; border:1px solid #e7eaf3; font-size:14px; padding:9px 12px; max-height:110px; }
    .wchat-send { width:44px; height:44px; border-radius:50%; background:#25D366; color:#fff; border:0; font-size:18px; flex-shrink:0; }
    .wchat-send:disabled { opacity:.5; }
    @media (max-width: 767px) {
        .wchat-wrap { flex-direction:column; height:auto; }
        .wchat-side { width:100%; max-height:260px; }
        .wchat-main { min-height:420px; }
    }
</style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title"><i class="tio-chat"></i> {{ translate('MyChitti WhatsApp Chats') }}</h1>
            <a href="{{ route('admin.business-settings.third-party.whatsapp-knowledge') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-book-opened"></i> {{ translate('Auto-Reply Knowledge') }}
            </a>
        </div>

        @if (!$connected)
            <div class="alert alert-warning">
                <b>{{ translate('Not configured.') }}</b> {{ translate('Set up the MyChitti WhatsApp number in') }}
                <a href="{{ route('admin.business-settings.third-party.whatsapp-config') }}" class="alert-link">{{ translate('WhatsApp API') }}</a>.
            </div>
        @else
            @if (!empty($subscribeError))
                <div class="alert alert-warning" style="font-size:13px;">
                    <i class="tio-info-outined"></i>
                    <b>{{ translate('Incoming messages may not arrive:') }}</b>
                    {{ translate('the MyChitti WhatsApp account could not be linked to the message receiver') }}
                    ({{ $subscribeError }}). {{ translate('Check the WhatsApp Business Account ID and token in') }}
                    <a href="{{ route('admin.business-settings.third-party.whatsapp-config') }}" class="alert-link">{{ translate('WhatsApp API') }}</a>.
                </div>
            @endif
            <div class="wchat-wrap">
                <div class="wchat-side">
                    <div class="wchat-side-head">
                        <input type="search" id="wchatSearch" class="form-control form-control-sm wchat-search" placeholder="{{ translate('Search name / number') }}">
                        {{-- Threads the bot promised a human follow-up on. The alert that fires at
                             the same time is deduped and easily missed, so this list is the one
                             durable place those promises can be found. --}}
                        <div class="wchat-filter">
                            <button type="button" class="wchat-tab on" data-filter="">{{ translate('All chats') }}</button>
                            <button type="button" class="wchat-tab" data-filter="1">
                                {{ translate('Needs reply') }} <span class="cnt" id="wchatWaitCount">0</span>
                            </button>
                        </div>
                    </div>
                    <div class="wchat-threads" id="wchatThreads">
                        <div class="text-center text-muted py-4" style="font-size:13px;">{{ translate('Loading chats…') }}</div>
                    </div>
                </div>

                <div class="wchat-main" id="wchatMain">
                    <div class="wchat-empty" id="wchatEmpty">
                        <i class="tio-chat" style="font-size:56px;opacity:.35;"></i>
                        <p class="mt-2 mb-0" style="font-size:14px;">{{ translate('Select a chat to view messages and reply.') }}</p>
                        <small style="font-size:12px;">{{ translate('Messages to the MyChitti WhatsApp number appear here automatically.') }}</small>
                    </div>

                    <div class="wchat-head" id="wchatHead" style="display:none;">
                        <div class="wchat-avatar" id="wchatHeadAvatar">?</div>
                        <div style="min-width:0;">
                            <div class="wchat-thread-name" id="wchatHeadName"></div>
                            <div class="wchat-thread-last" id="wchatHeadPhone"></div>
                        </div>
                    </div>

                    <div class="wchat-msgs" id="wchatMsgs" style="display:none;"></div>

                    <div class="wchat-window-note" id="wchatWindowNote" style="display:none;">
                        <i class="tio-time"></i> {{ translate('This contact last messaged more than 24 hours ago — WhatsApp may not deliver free-text replies outside the 24-hour window.') }}
                    </div>

                    <div class="wchat-pending" id="wchatPending">
                        <i class="tio-attachment"></i> <span id="wchatPendingName"></span>
                        <span class="text-muted" id="wchatPendingSize"></span>
                    </div>

                    <div class="wchat-input" id="wchatInput" style="display:none;">
                        {{-- WhatsApp fetches the file from a public link, so the upload has to land
                             before the send. The picker posts to send-media, which does both. --}}
                        <input type="file" id="wchatFile" style="display:none;"
                               accept=".jpg,.jpeg,.png,.webp,.mp4,.3gp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt">
                        <button type="button" class="wchat-attach" id="wchatAttach"
                                title="{{ translate('Attach image, video or document') }}"><i class="tio-attachment"></i></button>
                        <textarea id="wchatText" rows="1" placeholder="{{ translate('Type a message') }}"></textarea>
                        <button type="button" class="wchat-send" id="wchatSend" title="{{ translate('Send') }}"><i class="tio-send"></i></button>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('script_2')
@if ($connected)
<script>
(function () {
    var THREADS_URL = '{{ route('admin.business-settings.third-party.whatsapp-inbox.threads') }}';
    var THREAD_URL  = '{{ route('admin.business-settings.third-party.whatsapp-inbox.thread') }}';
    var SEND_URL    = '{{ route('admin.business-settings.third-party.whatsapp-inbox.send') }}';
    var MEDIA_URL   = '{{ route('admin.business-settings.third-party.whatsapp-inbox.send-media') }}';
    var CSRF        = '{{ csrf_token() }}';

    var threads = [];
    var activeKey = null;
    var lastRenderSignature = '';

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    function initials(t) {
        var base = (t.name || t.key || '?').trim();
        return esc(base.charAt(0).toUpperCase() || '?');
    }
    function fmtTime(iso) {
        if (!iso) return '';
        var d = new Date((iso + '').replace(' ', 'T'));
        if (isNaN(d)) return '';
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    function fmtDay(iso) {
        var d = new Date((iso + '').replace(' ', 'T'));
        if (isNaN(d)) return '';
        var today = new Date(); today.setHours(0,0,0,0);
        var that = new Date(d); that.setHours(0,0,0,0);
        var diff = (today - that) / 86400000;
        if (diff === 0) return 'Today';
        if (diff === 1) return 'Yesterday';
        return d.toLocaleDateString([], { day: 'numeric', month: 'short', year: 'numeric' });
    }
    function ticks(m) {
        if (m.direction !== 'out') return '';
        if (m.status === 'failed') return '<span class="wfail" title="' + esc(m.error || 'Failed') + '">✕ Not delivered' + (m.error ? ' — ' + esc(m.error) : '') + '</span>';
        var t = '✓', cls = '';
        if (m.status === 'delivered') t = '✓✓';
        if (m.status === 'read') { t = '✓✓'; cls = ' read'; }
        return '<span class="wticks' + cls + '">' + t + '</span>';
    }

    function renderThreads(list) {
        var q = ($('#wchatSearch').val() || '').toLowerCase();
        var html = '';
        list.forEach(function (t) {
            var label = t.name || ('+' + (t.phone || t.key).replace(/^\+/, ''));
            if (q && label.toLowerCase().indexOf(q) === -1 && t.key.indexOf(q) === -1) return;
            var kind = t.kind ? '<span class="wchat-kind ' + (t.kind === 'Vendor' ? 'vendor' : 'customer') + '">' + esc(t.kind) + '</span>' : '';
            var wait = t.needs_reply ? '<span class="wchat-wait">Needs reply</span>' : '';
            html += '<div class="wchat-thread' + (t.key === activeKey ? ' active' : '') + (t.needs_reply ? ' waiting' : '') + '" data-key="' + esc(t.key) + '" data-label="' + esc(label) + '" data-phone="' + esc(t.phone || t.key) + '">'
                + '<div class="wchat-avatar">' + initials(t) + '</div>'
                + '<div class="wchat-thread-main">'
                +   '<div class="wchat-thread-name">' + esc(label) + kind + wait + '</div>'
                +   '<div class="wchat-thread-last">' + (t.last_dir === 'out' ? '<span style="color:#53bdeb;">You: </span>' : '') + esc(t.last_body || '') + '</div>'
                + '</div>'
                + '<div class="wchat-thread-time">' + fmtTime(t.last_at) + '</div>'
                + '</div>';
        });
        $('#wchatThreads').html(html || '<div class="text-center text-muted py-4" style="font-size:13px;">No chats yet.</div>');
    }

    var needsReplyOnly = '';

    function loadThreads() {
        $.get(THREADS_URL, { needs_reply: needsReplyOnly }, function (res) {
            if (res && res.success) {
                threads = res.threads || [];
                // Always the unfiltered total, so the tab still shows what is waiting while
                // you are looking at it.
                $('#wchatWaitCount').text(res.waiting || 0);
                renderThreads(threads);
            }
        });
    }

    $(document).on('click', '.wchat-tab', function () {
        $('.wchat-tab').removeClass('on');
        $(this).addClass('on');
        needsReplyOnly = $(this).data('filter') + '';
        loadThreads();
    });

    function renderMessages(msgs) {
        var sig = msgs.map(function (m) { return m.id + ':' + (m.status || ''); }).join(',');
        if (sig === lastRenderSignature) return;
        lastRenderSignature = sig;

        var html = '', day = '';
        msgs.forEach(function (m) {
            var d = fmtDay(m.sent_at);
            if (d && d !== day) { day = d; html += '<div class="wchat-day"><span>' + esc(d) + '</span></div>'; }
            // An inbound attachment has no link stored - WhatsApp keeps those behind its own
            // media API - so only outbound files can be shown inline. Everything else falls
            // back to naming the type, as before.
            var media = '';
            if (m.media_url) {
                var u = esc(m.media_url);
                if (m.type === 'image') {
                    media = '<a href="' + u + '" target="_blank"><img class="wmedia" src="' + u + '"></a>';
                } else if (m.type === 'video') {
                    media = '<video class="wmedia" src="' + u + '" controls></video>';
                } else {
                    media = '<a class="wfile" href="' + u + '" target="_blank">'
                        + '<i class="tio-file-outlined"></i><span>' + esc(m.body || 'Document') + '</span></a>';
                }
            }

            var caption = m.body ? esc(m.body) : (media ? '' : '[' + esc(m.type || 'message') + ']');
            if (media && m.type === 'document') { caption = ''; }

            html += '<div class="wbubble ' + (m.direction === 'in' ? 'in' : 'out') + '">'
                + media + caption
                + '<div class="wmeta">' + fmtTime(m.sent_at) + ticks(m) + '</div>'
                + '</div>';
        });
        var $box = $('#wchatMsgs');
        $box.html(html);
        $box.scrollTop($box[0].scrollHeight);
    }

    function openThread(key, label, phone) {
        activeKey = key;
        lastRenderSignature = '';
        $('#wchatEmpty').hide();
        $('#wchatHead, #wchatMsgs, #wchatInput').show();
        $('#wchatHeadName').text(label);
        $('#wchatHeadPhone').text('+' + String(phone).replace(/^\+/, ''));
        $('#wchatHeadAvatar').text((label || '?').charAt(0).toUpperCase());
        $('#wchatMsgs').html('<div class="text-center text-muted py-3" style="font-size:13px;">Loading…</div>');
        renderThreads(threads);
        fetchThread();
        $('#wchatText').focus();
    }

    function fetchThread() {
        if (!activeKey) return;
        var key = activeKey;
        $.get(THREAD_URL, { phone: key }, function (res) {
            if (!res || !res.success || key !== activeKey) return;
            renderMessages(res.messages || []);
            $('#wchatWindowNote').toggle(!res.window_open);
        });
    }

    function send() {
        var text = ($('#wchatText').val() || '').trim();
        if (!text || !activeKey) return;
        $('#wchatSend').prop('disabled', true);
        $.post(SEND_URL, { _token: CSRF, phone: activeKey, message: text }, function (res) {
            $('#wchatSend').prop('disabled', false);
            if (res && res.success) {
                $('#wchatText').val('').trigger('input');
                lastRenderSignature = '';
                fetchThread();
                loadThreads();
            } else {
                toastr.error((res && res.error) || 'Message could not be sent.');
            }
        }).fail(function (xhr) {
            $('#wchatSend').prop('disabled', false);
            var msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) || 'Message could not be sent.';
            toastr.error(msg);
        });
    }

    function sendFile(file) {
        if (!file || !activeKey) { return; }

        $('#wchatPendingName').text(file.name);
        $('#wchatPendingSize').text('(' + (file.size / 1048576).toFixed(1) + ' MB) uploading...');
        $('#wchatPending').css('display', 'flex');
        $('#wchatAttach, #wchatSend').prop('disabled', true);

        var fd = new FormData();
        fd.append('_token', CSRF);
        fd.append('phone', activeKey);
        fd.append('file', file);
        // Whatever is already typed rides along as the caption, the way WhatsApp itself does it.
        fd.append('caption', ($('#wchatText').val() || '').trim());

        $.ajax({ url: MEDIA_URL, method: 'POST', data: fd, processData: false, contentType: false })
            .done(function (res) {
                if (res && res.success) {
                    $('#wchatText').val('').trigger('input');
                    lastRenderSignature = '';
                    fetchThread();
                    loadThreads();
                } else {
                    toastr.error((res && res.error) || 'File could not be sent.');
                }
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message))
                    || 'File could not be sent.';
                toastr.error(msg);
            })
            .always(function () {
                $('#wchatPending').hide();
                $('#wchatAttach, #wchatSend').prop('disabled', false);
                // Cleared so picking the same file twice in a row still fires change.
                $('#wchatFile').val('');
            });
    }

    $('#wchatAttach').on('click', function () { $('#wchatFile').trigger('click'); });
    $('#wchatFile').on('change', function () { sendFile(this.files && this.files[0]); });

    $(document).on('click', '.wchat-thread', function () {
        openThread($(this).data('key') + '', $(this).data('label') + '', $(this).data('phone') + '');
    });
    $('#wchatSearch').on('input', function () { renderThreads(threads); });
    $('#wchatSend').on('click', send);
    $('#wchatText').on('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
    }).on('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 110) + 'px';
    });

    loadThreads();
    setInterval(loadThreads, 15000);
    setInterval(fetchThread, 8000);
})();
</script>
@endif
@endpush
