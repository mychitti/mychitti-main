@extends('layouts.vendor.app')

@section('title', 'WhatsApp Chats')

@push('css_or_js')
<style>
    .wchat-wrap { display:flex; height:calc(100vh - 220px); min-height:480px; border:1px solid #e7eaf3; border-radius:10px; overflow:hidden; background:#fff; }
    /* ── Left: conversation list ── */
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
    /* ── Right: chat ── */
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
    /* Forward-to-staff control, shown on bubble hover */
    .wbubble .wfwd { position:absolute; top:2px; right:4px; border:0; background:transparent; color:#54656f;
        font-size:14px; line-height:1; padding:2px 4px; border-radius:4px; opacity:0; transition:opacity .15s; cursor:pointer; }
    .wbubble:hover .wfwd { opacity:.85; }
    .wbubble .wfwd:hover { opacity:1; background:rgba(0,0,0,.06); }
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
            <h1 class="page-header-title"><i class="tio-chat"></i> WhatsApp Chats</h1>
            <a href="{{ route('vendor.whatsapp.dashboard') }}" class="btn btn-sm btn-outline-primary"><i class="tio-dashboard-outlined"></i> Dashboard</a>
        </div>

        @if (!$connected)
            <div class="alert alert-warning">
                <b>Not connected.</b> Connect your own WhatsApp number to see and reply to customer chats.
                <a href="{{ route('vendor.whatsapp.connect') }}" class="alert-link">Connect now →</a>
            </div>
        @else
            @if (!empty($subscribeError))
                <div class="alert alert-warning" style="font-size:13px;">
                    <i class="tio-info-outined"></i>
                    <b>Incoming messages may not arrive:</b> your WhatsApp account could not be linked to our message receiver
                    ({{ $subscribeError }}). Try disconnecting and connecting WhatsApp again from the
                    <a href="{{ route('vendor.whatsapp.connect') }}" class="alert-link">Connection</a> page.
                </div>
            @endif
            <div class="wchat-wrap">
                {{-- Conversation list --}}
                <div class="wchat-side">
                    <div class="wchat-side-head">
                        <input type="search" id="wchatSearch" class="form-control form-control-sm wchat-search" placeholder="Search or start typing a name / number">
                    </div>
                    <div class="wchat-threads" id="wchatThreads">
                        <div class="text-center text-muted py-4" style="font-size:13px;">Loading chats…</div>
                    </div>
                </div>

                {{-- Chat area --}}
                <div class="wchat-main" id="wchatMain">
                    <div class="wchat-empty" id="wchatEmpty">
                        <i class="tio-chat" style="font-size:56px;opacity:.35;"></i>
                        <p class="mt-2 mb-0" style="font-size:14px;">Select a chat to view messages and reply.</p>
                        <small style="font-size:12px;">Customer messages to your WhatsApp number appear here automatically.</small>
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
                        <i class="tio-time"></i> This customer last messaged more than 24 hours ago — WhatsApp may not deliver
                        free-text replies outside the 24-hour window. If your message fails, use an approved template from the
                        <a href="{{ route('vendor.whatsapp.connect') }}">bulk message</a> screen instead.
                    </div>

                    <div class="wchat-input" id="wchatInput" style="display:none;">
                        <textarea id="wchatText" rows="1" placeholder="Type a message"></textarea>
                        <button type="button" class="wchat-send" id="wchatSend" title="Send"><i class="tio-send"></i></button>
                    </div>
                </div>
            </div>

            {{-- Forward-to-staff modal --}}
            <div class="modal fade" id="wfwdModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="tio-share"></i> Forward to Staff</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="input-label">Staff member</label>
                                <select id="wfwdStaff" class="form-control">
                                    <option value="">Loading staff…</option>
                                </select>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-sm-6">
                                    <label class="input-label">From (name)</label>
                                    <input type="text" id="wfwdName" class="form-control" maxlength="200">
                                </div>
                                <div class="form-group col-sm-6">
                                    <label class="input-label">Phone</label>
                                    <input type="text" id="wfwdPhone" class="form-control" maxlength="40">
                                </div>
                            </div>
                            <div class="form-group mb-1">
                                <label class="input-label">Message</label>
                                <textarea id="wfwdText" class="form-control" rows="5" style="font-size:13px;"></textarea>
                            </div>
                            <small class="text-muted" style="font-size:11.5px;">
                                <i class="tio-info-outined"></i> Sent from your WhatsApp number using the
                                <b>Forward to Staff</b> template. Submit and get it approved on the
                                <a href="{{ route('vendor.whatsapp.templates') }}">Templates</a> page first. Until then it
                                falls back to free text, which only delivers if the staff member messaged your number in the last 24 hours.
                            </small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white btn-sm" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn--primary btn-sm" id="wfwdSend"><i class="tio-send"></i> Forward</button>
                        </div>
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
    var THREADS_URL = '{{ route('vendor.whatsapp.inbox.threads') }}';
    var THREAD_URL  = '{{ route('vendor.whatsapp.inbox.thread') }}';
    var SEND_URL    = '{{ route('vendor.whatsapp.inbox.send') }}';
    var STAFF_URL   = '{{ route('vendor.whatsapp.inbox.staff') }}';
    var FORWARD_URL = '{{ route('vendor.whatsapp.inbox.forward') }}';
    var CSRF        = '{{ csrf_token() }}';

    var threads = [];
    var activeKey = null;
    var activeLabel = '';
    var activePhone = '';
    var staffLoaded = false;
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
            html += '<div class="wchat-thread' + (t.key === activeKey ? ' active' : '') + '" data-key="' + esc(t.key) + '" data-label="' + esc(label) + '" data-phone="' + esc(t.phone || t.key) + '">'
                + '<div class="wchat-avatar">' + initials(t) + '</div>'
                + '<div class="wchat-thread-main">'
                +   '<div class="wchat-thread-name">' + esc(label) + '</div>'
                +   '<div class="wchat-thread-last">' + (t.last_dir === 'out' ? '<span style="color:#53bdeb;">You: </span>' : '') + esc(t.last_body || '') + '</div>'
                + '</div>'
                + '<div class="wchat-thread-time">' + fmtTime(t.last_at) + '</div>'
                + '</div>';
        });
        $('#wchatThreads').html(html || '<div class="text-center text-muted py-4" style="font-size:13px;">No chats yet.<br><small>When customers message your WhatsApp number, chats appear here.</small></div>');
    }

    function loadThreads() {
        $.get(THREADS_URL, function (res) {
            if (res && res.success) { threads = res.threads || []; renderThreads(threads); }
        });
    }

    function renderMessages(msgs) {
        // Signature covers every message's status — a delivered/read tick landing on an
        // EARLIER bubble must trigger a re-render too, not just new messages.
        var sig = msgs.map(function (m) { return m.id + ':' + (m.status || ''); }).join(',');
        if (sig === lastRenderSignature) return;
        lastRenderSignature = sig;

        var html = '', day = '';
        msgs.forEach(function (m) {
            var d = fmtDay(m.sent_at);
            if (d && d !== day) { day = d; html += '<div class="wchat-day"><span>' + esc(d) + '</span></div>'; }
            var body = m.body || '[' + (m.type || 'message') + ']';
            html += '<div class="wbubble ' + (m.direction === 'in' ? 'in' : 'out') + '">'
                + '<button type="button" class="wfwd" title="Forward to staff" data-body="' + esc(body) + '">↪</button>'
                + esc(body)
                + '<div class="wmeta">' + fmtTime(m.sent_at) + ticks(m) + '</div>'
                + '</div>';
        });
        var $box = $('#wchatMsgs');
        $box.html(html);
        $box.scrollTop($box[0].scrollHeight);
    }

    function openThread(key, label, phone) {
        activeKey = key;
        activeLabel = label;
        activePhone = phone;
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

    // ── Forward to staff ─────────────────────────────────────
    function loadStaffOnce() {
        if (staffLoaded) return;
        staffLoaded = true;
        $.get(STAFF_URL, function (res) {
            var opts = '<option value="">Select staff member…</option>';
            if (res && res.success) {
                (res.staff || []).forEach(function (s) {
                    opts += '<option value="' + s.id + '">' + esc(s.name) + '</option>';
                });
                if (!(res.staff || []).length) {
                    opts = '<option value="">No staff with a phone number on file</option>';
                }
            }
            $('#wfwdStaff').html(opts);
        }).fail(function () {
            staffLoaded = false;
            $('#wfwdStaff').html('<option value="">Could not load staff</option>');
        });
    }

    function openForward(body) {
        if (!activeKey) return;
        loadStaffOnce();
        $('#wfwdName').val(activeLabel || 'Customer');
        $('#wfwdPhone').val('+' + String(activePhone || activeKey).replace(/^\+/, ''));
        $('#wfwdText').val(body);
        $('#wfwdStaff').val('');
        $('#wfwdModal').modal('show');
    }

    function sendForward() {
        var staffId = $('#wfwdStaff').val();
        var name    = ($('#wfwdName').val() || '').trim();
        var phone   = ($('#wfwdPhone').val() || '').trim();
        var text    = ($('#wfwdText').val() || '').trim();
        if (!staffId) { toastr.error('Choose a staff member to forward to.'); return; }
        if (!text)    { toastr.error('The message is empty.'); return; }

        $('#wfwdSend').prop('disabled', true);
        $.post(FORWARD_URL, { _token: CSRF, staff_id: staffId, sender_name: name, sender_phone: phone, message: text }, function (res) {
            $('#wfwdSend').prop('disabled', false);
            if (res && res.success) {
                $('#wfwdModal').modal('hide');
                toastr.success('Forwarded to ' + (res.staff || 'staff') + '.');
            } else {
                toastr.error((res && res.error) || 'Could not forward the message.');
            }
        }).fail(function (xhr) {
            $('#wfwdSend').prop('disabled', false);
            var msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) || 'Could not forward the message.';
            toastr.error(msg);
        });
    }

    $(document).on('click', '.wfwd', function (e) {
        e.stopPropagation();
        openForward($(this).data('body') + '');
    });
    $('#wfwdSend').on('click', sendForward);

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
