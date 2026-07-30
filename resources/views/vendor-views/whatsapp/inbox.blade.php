@extends('layouts.vendor.app')

@section('title', 'WhatsApp Chats')

@push('css_or_js')
    @include('vendor-views.whatsapp.partials._ui')
<style>
    .wchat-wrap {
        display:flex; height:calc(100vh - 240px); min-height:520px;
        border:1px solid var(--wa-line); border-radius:16px; overflow:hidden; background:#fff;
        box-shadow:0 1px 2px rgba(16,24,40,.04), 0 8px 24px -12px rgba(16,24,40,.12);
    }

    /* Thin, unobtrusive scrollbars — the default chrome ones are heavy next to this density. */
    .wchat-threads::-webkit-scrollbar, .wchat-msgs::-webkit-scrollbar { width:6px; }
    .wchat-threads::-webkit-scrollbar-thumb, .wchat-msgs::-webkit-scrollbar-thumb { background:rgba(11,20,26,.16); border-radius:6px; }
    .wchat-threads::-webkit-scrollbar-thumb:hover, .wchat-msgs::-webkit-scrollbar-thumb:hover { background:rgba(11,20,26,.28); }

    /* ── Left: conversation list ── */
    .wchat-side { width:336px; min-width:280px; border-right:1px solid var(--wa-line); display:flex; flex-direction:column; background:#fff; }
    .wchat-side-head { padding:14px 14px 0; border-bottom:1px solid var(--wa-line); background:#fff; }
    .wchat-search-wrap { position:relative; }
    .wchat-search-wrap > i {
        position:absolute; left:13px; top:50%; transform:translateY(-50%);
        color:var(--wa-mute); font-size:15px; pointer-events:none;
    }
    .wchat-search {
        border-radius:20px; font-size:13px; padding-left:36px; background:#f4f6f8; border-color:transparent;
        transition:background .15s, border-color .15s, box-shadow .15s;
    }
    .wchat-search:focus { background:#fff; border-color:var(--wa-green); box-shadow:0 0 0 3px rgba(37,211,102,.14); }
    .wchat-filters { display:flex; gap:2px; margin-top:12px; }
    .wchat-filters button {
        border:0; background:none; border-bottom:2px solid transparent; padding:9px 10px;
        font-size:12.5px; font-weight:600; color:var(--wa-mute); cursor:pointer; transition:color .15s;
    }
    .wchat-filters button:hover { color:var(--wa-ink); }
    .wchat-filters button.active { color:var(--wa-green-d); border-bottom-color:var(--wa-green); }
    .wchat-threads { flex:1; overflow-y:auto; }
    .wchat-thread {
        display:flex; gap:11px; padding:12px 14px; cursor:pointer; align-items:center;
        border-bottom:1px solid #f5f6f8; position:relative; transition:background .12s;
    }
    .wchat-thread:hover { background:#f7f8fa; }
    /* Active gets an accent rail rather than a flat grey fill — reads as selection, not disabled. */
    .wchat-thread.active { background:#f0fdf4; }
    .wchat-thread.active::before { content:''; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--wa-green); }
    .wchat-avatar {
        width:44px; height:44px; border-radius:50%; color:#fff; display:flex; align-items:center;
        justify-content:center; font-weight:600; font-size:16px; flex-shrink:0; letter-spacing:.3px;
        background:var(--wa-green);
    }
    .wchat-thread-main { min-width:0; flex:1; }
    .wchat-thread-name { font-weight:600; font-size:14px; color:#111b21; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .wchat-thread-last { font-size:12.5px; color:#667781; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:1px; }
    .wchat-thread-meta { display:flex; flex-direction:column; align-items:flex-end; gap:5px; flex-shrink:0; }
    .wchat-thread-time { font-size:11px; color:#8696a0; white-space:nowrap; }
    /* A customer spoke last — nobody has replied yet. The one signal a vendor actually needs. */
    .wchat-dot { width:9px; height:9px; border-radius:50%; background:var(--wa-green); box-shadow:0 0 0 3px rgba(37,211,102,.18); }

    /* Skeletons while the first load runs — steadier than a line of text that pops away. */
    .wchat-skel { display:flex; gap:11px; padding:12px 14px; align-items:center; }
    .wchat-skel span { display:block; border-radius:6px; background:linear-gradient(90deg,#eef1f4 25%,#f7f9fa 37%,#eef1f4 63%); background-size:400% 100%; animation:wchatShimmer 1.3s ease infinite; }
    .wchat-skel .s-av { width:44px; height:44px; border-radius:50%; flex-shrink:0; }
    .wchat-skel .s-l1 { height:11px; width:55%; margin-bottom:7px; }
    .wchat-skel .s-l2 { height:10px; width:80%; }
    @keyframes wchatShimmer { 0% { background-position:100% 50%; } 100% { background-position:0 50%; } }

    /* ── Right: chat ── */
    .wchat-main { flex:1; display:flex; flex-direction:column; min-width:0; background:#efeae2; }

    /* Chat header: dark teal bar, customer avatar, name and number. */
    .wchat-head {
        padding:9px 14px; background:#075e54; border-bottom:0;
        display:flex; align-items:center; gap:11px; color:#fff;
    }
    .wchat-head .wchat-avatar { background:rgba(255,255,255,.22); color:#fff; width:40px; height:40px; font-size:15px; }
    .wchat-head-name {
        font-weight:600; font-size:15px; color:#fff; line-height:1.25;
        display:flex; align-items:center; gap:5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .wchat-head-sub { font-size:12px; color:rgba(255,255,255,.72); line-height:1.3; }
    .wchat-head-actions { margin-left:auto; display:flex; align-items:center; gap:2px; flex-shrink:0; }
    .wchat-head-actions button {
        border:0; background:none; color:rgba(255,255,255,.9); font-size:17px;
        width:34px; height:34px; border-radius:50%; line-height:1;
    }
    .wchat-head-actions button:hover { background:rgba(255,255,255,.14); color:#fff; }
    .wchat-back { display:none; border:0; background:none; font-size:20px; color:#fff; padding:0 2px; }
    .wchat-msgs { flex:1; overflow-y:auto; padding:18px 7% 12px; scroll-behavior:smooth;
        background-color:#efeae2;
        background-image:radial-gradient(#d9d3c8 0.6px, transparent 0.6px);
        background-size:22px 22px; }
    .wchat-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; color:#667781; background:#f0f2f5; padding:24px; }

    /* Jump-to-latest, shown only when the vendor has scrolled up. */
    .wchat-jump {
        position:absolute; right:18px; bottom:84px; width:38px; height:38px; border-radius:50%;
        background:#fff; color:#54656f; border:1px solid var(--wa-line); font-size:16px;
        box-shadow:0 2px 8px rgba(11,20,26,.16); display:none; z-index:3;
    }
    .wchat-jump:hover { color:var(--wa-green-d); }
    .wbubble { width:fit-content; min-width:110px; max-width:72%; margin-bottom:8px; padding:6px 9px 7px; border-radius:8px; font-size:13.5px; line-height:1.45;
        box-shadow:0 1px 0.5px rgba(11,20,26,.13); position:relative; white-space:pre-wrap; word-wrap:break-word;
        animation:wbubbleIn .18s ease-out both; }
    @keyframes wbubbleIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }
    /* Optimistic bubble: shown the instant Send is pressed, replaced when the server answers. */
    .wbubble.pending { opacity:.72; }
    .wbubble.in  { background:#fff;    margin-right:auto; border-top-left-radius:0; }
    .wbubble.out { background:#d9fdd3; margin-left:auto;  border-top-right-radius:0; }
    /* Tails — the small notch that makes a bubble read as speech rather than a box. */
    .wbubble::before { content:''; position:absolute; top:0; width:0; height:0; border:6px solid transparent; }
    .wbubble.in::before  { left:-6px;  border-top-color:#fff;    border-right-width:0; }
    .wbubble.out::before { right:-6px; border-top-color:#d9fdd3; border-left-width:0; }
    /* Consecutive messages from the same side: drop the tail and tighten the gap. */
    .wbubble.cont { margin-top:-4px; }
    .wbubble.cont.in  { border-top-left-radius:8px; }
    .wbubble.cont.out { border-top-right-radius:8px; }
    .wbubble.cont::before { display:none; }

    /* Centred system line — delivery confirmations and the like, as in WhatsApp itself. */
    .wchat-system { text-align:center; margin:10px 0; }
    .wchat-system span {
        display:inline-flex; align-items:center; gap:6px; background:#d9fdd3; color:#3c6e47;
        font-size:11.5px; padding:5px 12px; border-radius:8px; box-shadow:0 1px 0.5px rgba(11,20,26,.13);
    }

    /* A tag above a bubble naming what kind of message it was — a template campaign, a
       reminder, an auto-reply. Sent from the same number, but not the same thing at all. */
    .wbubble .wtag {
        display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:700;
        letter-spacing:.3px; text-transform:uppercase; color:#3c6e47;
        background:rgba(37,211,102,.16); border-radius:5px; padding:2px 6px; margin-bottom:4px;
    }
    .wbubble.in .wtag { color:#0369a1; background:rgba(14,165,233,.14); }

    /* The customer tapped a button / picked from a list, rather than typing. */
    .wbubble.reply { border-left:3px solid #25d366; padding-left:10px; }
    .wbubble.reply .wreply-lbl { font-size:10.5px; color:#3c6e47; font-weight:600; display:block; margin-bottom:2px; }

    /* Media we can see the type of but not the contents — Meta keeps the file behind its own
       CDN and we never download it, so name the attachment rather than pretend to preview it. */
    .wbubble .wmedia { display:flex; align-items:center; gap:9px; padding:2px 0; }
    .wbubble .wmedia-ico {
        width:34px; height:34px; border-radius:8px; flex-shrink:0; display:flex; align-items:center;
        justify-content:center; font-size:16px; background:rgba(11,20,26,.06); color:#54656f;
    }
    .wbubble .wmedia-t { font-size:13px; font-weight:600; color:#111b21; }
    .wbubble .wmedia-s { font-size:11px; color:#667781; }
    .wbubble .wmeta { font-size:10.5px; color:#667781; text-align:right; margin-top:2px; user-select:none; }
    .wbubble .wticks { letter-spacing:-2px; margin-left:3px; }
    .wbubble .wticks.read { color:#53bdeb; }
    .wbubble .wfail { color:#dc3545; font-size:11px; display:block; text-align:right; }
    .wbubble .wfwd { position:absolute; top:2px; right:4px; border:0; background:transparent; color:#54656f;
        font-size:14px; line-height:1; padding:2px 4px; border-radius:4px; opacity:0; transition:opacity .15s; cursor:pointer; }
    .wbubble:hover .wfwd { opacity:.85; }
    .wbubble .wfwd:hover { opacity:1; background:rgba(0,0,0,.06); }
    /* Day separators stay pinned while their day scrolls past. */
    .wchat-day { text-align:center; margin:12px 0; position:sticky; top:0; z-index:2; }
    .wchat-day span { background:#fff; color:#54656f; font-size:11px; font-weight:600; padding:4px 11px; border-radius:8px; box-shadow:0 1px 0.5px rgba(11,20,26,.13); }
    .wchat-window-note { background:#fff8e1; color:#7a6a1f; font-size:12px; padding:9px 14px; border-top:1px solid #f0e6bb; }
    .wchat-input { display:flex; gap:9px; padding:11px 14px; background:#f0f2f5; align-items:flex-end; }
    .wchat-input textarea {
        flex:1; resize:none; border-radius:22px; border:1px solid transparent; background:#fff;
        font-size:14px; padding:11px 16px; max-height:120px; line-height:1.4;
        transition:border-color .15s, box-shadow .15s;
    }
    .wchat-input textarea:focus { outline:0; border-color:var(--wa-green); box-shadow:0 0 0 3px rgba(37,211,102,.14); }
    .wchat-send {
        width:44px; height:44px; border-radius:50%; background:var(--wa-green); color:#fff; border:0;
        font-size:18px; flex-shrink:0; transition:transform .12s, background .15s, opacity .15s;
        box-shadow:0 2px 6px rgba(37,211,102,.35);
    }
    .wchat-send:hover:not(:disabled) { background:var(--wa-green-d); transform:scale(1.06); }
    .wchat-send:active:not(:disabled) { transform:scale(.96); }
    .wchat-send:disabled { opacity:.45; box-shadow:none; }

    /* Mobile: one pane at a time. Tapping a chat slides the list out of the way instead of
       leaving a cramped 260px list stacked above an equally cramped conversation. */
    @media (max-width: 767.98px) {
        .wchat-wrap { height:calc(100vh - 190px); min-height:440px; }
        .wchat-side { width:100%; min-width:0; border-right:0; }
        .wchat-main { display:none; }
        .wchat-back { display:block; }
        .wchat-wrap.chat-open .wchat-side { display:none; }
        .wchat-wrap.chat-open .wchat-main { display:flex; }
    }
</style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
            <div>
                <h1 class="page-header-title mb-0"><i class="tio-chat"></i> WhatsApp Chats</h1>
                <span class="wa-sub">Two-way conversations on your connected number.</span>
            </div>
            <a href="{{ route('vendor.whatsapp.dashboard') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-dashboard-outlined"></i> Dashboard
            </a>
        </div>

        @if (!$connected)
            <div class="wa-card">
                <div class="wa-card-b">
                    <div class="wa-empty">
                        <i class="tio-chat-outlined"></i>
                        <div class="wa-empty-t">No inbox yet</div>
                        <div class="wa-empty-s mb-3">
                            Customer chats arrive on your own WhatsApp number. Connect one to read and reply here.
                        </div>
                        <a href="{{ route('vendor.whatsapp.connect') }}" class="btn btn-sm btn--primary">Connect WhatsApp</a>
                    </div>
                </div>
            </div>
        @else
            @if (!empty($subscribeError))
                <div class="alert alert-warning d-flex align-items-start" style="font-size:13px;gap:10px;">
                    <i class="tio-warning mt-1"></i>
                    <div>
                        <b>Incoming messages may not arrive.</b>
                        Your WhatsApp account could not be linked to our message receiver ({{ $subscribeError }}).
                        Disconnect and reconnect from the
                        <a href="{{ route('vendor.whatsapp.connect') }}" class="alert-link">Connection</a> page to fix it.
                    </div>
                </div>
            @endif

            <div class="wchat-wrap" id="wchatWrap">
                {{-- Conversation list --}}
                <div class="wchat-side">
                    <div class="wchat-side-head">
                        <div class="wchat-search-wrap">
                            <i class="tio-search"></i>
                            <input type="search" id="wchatSearch" class="form-control form-control-sm wchat-search"
                                   placeholder="Search by name or number">
                        </div>
                        <div class="wchat-filters">
                            <button type="button" class="wchat-filter active" data-filter="all">All</button>
                            <button type="button" class="wchat-filter" data-filter="unreplied">
                                Needs reply <span id="wchatUnrepliedCount"></span>
                            </button>
                        </div>
                    </div>
                    <div class="wchat-threads" id="wchatThreads">
                        @for ($i = 0; $i < 6; $i++)
                            <div class="wchat-skel">
                                <span class="s-av"></span>
                                <div style="flex:1;min-width:0;"><span class="s-l1"></span><span class="s-l2"></span></div>
                            </div>
                        @endfor
                    </div>
                </div>

                {{-- Chat area --}}
                <div class="wchat-main" id="wchatMain" style="position:relative;">
                    <div class="wchat-empty" id="wchatEmpty">
                        <i class="tio-chat" style="font-size:52px;opacity:.3;"></i>
                        <p class="mt-2 mb-0" style="font-size:14px;font-weight:600;color:#111b21;">Select a chat to reply</p>
                        <small style="font-size:12px;">Messages customers send to your WhatsApp number appear here automatically.</small>
                    </div>

                    <div class="wchat-head" id="wchatHead" style="display:none;">
                        <button type="button" class="wchat-back" id="wchatBack" title="Back to chats"><i class="tio-chevron-left"></i></button>
                        <div class="wchat-avatar" id="wchatHeadAvatar">?</div>
                        <div style="min-width:0;flex:1;">
                            {{-- The header is the CUSTOMER, not your business — no verified badge
                                 and no "business account" label, because they are neither. --}}
                            <div class="wchat-head-name">
                                <span id="wchatHeadName" style="overflow:hidden;text-overflow:ellipsis;"></span>
                            </div>
                            <div class="wchat-head-sub" id="wchatHeadPhone"></div>
                        </div>
                        <div class="wchat-head-actions">
                            <button type="button" id="wchatRefresh" title="Refresh this chat"><i class="tio-refresh"></i></button>
                        </div>
                    </div>

                    <div class="wchat-msgs" id="wchatMsgs" style="display:none;"></div>
                    <button type="button" class="wchat-jump" id="wchatJump" title="Jump to latest"><i class="tio-chevron-down"></i></button>

                    <div class="wchat-window-note" id="wchatWindowNote" style="display:none;">
                        <i class="tio-time"></i> Last message from this customer was over 24 hours ago — WhatsApp may not
                        deliver free-text replies outside that window. If yours fails, send an approved template from the
                        <a href="{{ route('vendor.whatsapp.connect') }}">bulk message</a> screen instead.
                    </div>

                    <div class="wchat-input" id="wchatInput" style="display:none;">
                        <textarea id="wchatText" rows="1" placeholder="Type a message"></textarea>
                        <button type="button" class="wchat-send" id="wchatSend" title="Send"><i class="tio-send"></i></button>
                    </div>
                </div>
            </div>

            <small class="wa-sub d-block mt-2">
                <i class="tio-info-outined"></i> Hover any message to forward it to a staff member.
            </small>

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
    var filter = 'all';

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    function initials(t) {
        var base = (t.name || t.key || '?').trim();
        return esc(base.charAt(0).toUpperCase() || '?');
    }
    // A stable colour per contact. All-green avatars made every row look identical; a hue
    // derived from the number gives the list a scannable rhythm without inventing any data.
    var AVATAR_HUES = ['#0ea5e9', '#8b5cf6', '#f59e0b', '#ef4444', '#10b981', '#ec4899', '#6366f1', '#14b8a6'];
    function avatarColor(key) {
        var s = String(key || ''), h = 0;
        for (var i = 0; i < s.length; i++) { h = (h * 31 + s.charCodeAt(i)) % 9973; }
        return AVATAR_HUES[h % AVATAR_HUES.length];
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

    // The customer sent the last message, so the ball is in the vendor's court.
    function needsReply(t) { return t.last_dir === 'in'; }

    var MEDIA = {
        image:    { icon: 'tio-image', label: 'Photo' },
        video:    { icon: 'tio-video-camera', label: 'Video' },
        audio:    { icon: 'tio-volume-up', label: 'Voice message' },
        voice:    { icon: 'tio-volume-up', label: 'Voice message' },
        document: { icon: 'tio-attachment', label: 'Document' },
        sticker:  { icon: 'tio-sentiment-very-satisfied', label: 'Sticker' },
        location: { icon: 'tio-poi', label: 'Location' }
    };
    // What kind of message this was, said plainly. Campaigns, reminders and auto-replies all
    // leave the same number, and a wall of identical bubbles hides which is which.
    //
    // Only the ones that aren't obvious get a tag. A reply the vendor typed themselves is the
    // ordinary case — labelling every one of those REPLY is noise, not information.
    var CONTEXT_TAG = {
        'bulk':          'Campaign',
        'nearby':        'Nearby offer',
        'welcome':       'Welcome',
        'auto reply':    'AI auto-reply',
        'test message':  'Test',
        'lead notify':   'Lead alert',
        'lead accepted': 'Lead accepted'
    };

    function messageKind(m) {
        var type = (m.type || 'text').toLowerCase();
        var body = m.body || '';
        var ctx  = (m.context || '').toLowerCase();
        var placeholder = /^\[[a-z_]+\]$/i.test(body.trim());

        // Attachment: Meta keeps the file on its own CDN and we never download it, so name it
        // rather than show a broken preview. Any caption we did get still shows.
        if (MEDIA[type]) {
            var media = MEDIA[type];
            return {
                cls: '',
                tag: '',
                html: '<div class="wmedia"><div class="wmedia-ico"><i class="' + media.icon + '"></i></div>'
                    + '<div><div class="wmedia-t">' + media.label + '</div>'
                    + '<div class="wmedia-s">Opens in WhatsApp on your phone</div></div></div>'
                    + (placeholder ? '' : '<div style="margin-top:5px;">' + esc(body) + '</div>')
            };
        }

        // The customer tapped a button or picked from a list instead of typing.
        if (type === 'button' || type === 'interactive') {
            return {
                cls: 'reply',
                tag: '',
                html: '<span class="wreply-lbl">↩ Chose an option</span>'
                    + esc(placeholder ? 'Selection received' : body)
            };
        }

        var tag = ctx.indexOf('appt reminder') === 0 ? 'Reminder' : CONTEXT_TAG[ctx];
        return { cls: '', tag: tag ? esc(tag) : '', html: esc(body) };
    }

    function renderThreads(list) {
        var q = ($('#wchatSearch').val() || '').toLowerCase();
        var waiting = list.filter(needsReply).length;
        $('#wchatUnrepliedCount').html(waiting ? '<span class="wa-chip badge-soft-success ml-1">' + waiting + '</span>' : '');

        var html = '', shown = 0;
        list.forEach(function (t) {
            var label = t.name || ('+' + (t.phone || t.key).replace(/^\+/, ''));
            if (q && label.toLowerCase().indexOf(q) === -1 && t.key.indexOf(q) === -1) return;
            if (filter === 'unreplied' && !needsReply(t)) return;
            shown++;
            html += '<div class="wchat-thread' + (t.key === activeKey ? ' active' : '') + '" data-key="' + esc(t.key) + '" data-label="' + esc(label) + '" data-phone="' + esc(t.phone || t.key) + '">'
                + '<div class="wchat-avatar" style="background:' + avatarColor(t.key) + ';">' + initials(t) + '</div>'
                + '<div class="wchat-thread-main">'
                +   '<div class="wchat-thread-name">' + esc(label) + '</div>'
                +   '<div class="wchat-thread-last">' + (t.last_dir === 'out' ? '<span style="color:#53bdeb;">You: </span>' : '') + esc(t.last_body || '') + '</div>'
                + '</div>'
                + '<div class="wchat-thread-meta">'
                +   '<span class="wchat-thread-time">' + fmtTime(t.last_at) + '</span>'
                +   (needsReply(t) ? '<span class="wchat-dot" title="Waiting on your reply"></span>' : '')
                + '</div>'
                + '</div>';
        });

        if (!shown) {
            html = '<div class="wa-empty"><i class="tio-chat-outlined"></i>'
                + '<div class="wa-empty-t">' + (q || filter === 'unreplied' ? 'Nothing matches' : 'No chats yet') + '</div>'
                + '<div class="wa-empty-s">' + (q || filter === 'unreplied'
                    ? 'Try a different search or switch back to All.'
                    : 'When customers message your WhatsApp number, chats appear here.') + '</div></div>';
        }
        $('#wchatThreads').html(html);
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

        var html = '', day = '', prevDir = null;
        msgs.forEach(function (m) {
            var d = fmtDay(m.sent_at);
            if (d && d !== day) {
                day = d; prevDir = null;
                html += '<div class="wchat-day"><span>' + esc(d) + '</span></div>';
            }
            var dir = m.direction === 'in' ? 'in' : 'out';
            var body = m.body || '[' + (m.type || 'message') + ']';
            // A run from the same side reads as one turn: only the first bubble gets a tail.
            var cont = dir === prevDir ? ' cont' : '';
            prevDir = dir;

            var kind = messageKind(m);
            html += '<div class="wbubble ' + dir + cont + (kind.cls ? ' ' + kind.cls : '') + '">'
                + '<button type="button" class="wfwd" title="Forward to staff" data-body="' + esc(body) + '">↪</button>'
                + (kind.tag ? '<span class="wtag">' + kind.tag + '</span>' : '')
                + kind.html
                + '<div class="wmeta">' + fmtTime(m.sent_at) + ticks(m) + '</div>'
                + '</div>';
        });
        var box = document.getElementById('wchatMsgs');
        // Don't yank the view down if they've scrolled up to read history.
        var atBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 120;
        box.innerHTML = html;
        if (atBottom) box.scrollTop = box.scrollHeight;
    }

    function openThread(key, label, phone) {
        activeKey = key;
        activeLabel = label;
        activePhone = phone;
        lastRenderSignature = '';
        $('#wchatWrap').addClass('chat-open');
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

        // Show the message immediately and clear the box — waiting on a round trip before any
        // feedback is what made this feel slow. The server's copy replaces it on the next fetch.
        var $pending = $('<div class="wbubble out pending"></div>')
            .text(text)
            .append('<div class="wmeta">' + fmtTime(new Date().toISOString()) + ' <i class="tio-time"></i></div>');
        $('#wchatMsgs').append($pending);
        scrollToLatest();

        $('#wchatText').val('').trigger('input');
        $('#wchatSend').prop('disabled', true);

        $.post(SEND_URL, { _token: CSRF, phone: activeKey, message: text }, function (res) {
            $('#wchatSend').prop('disabled', false);
            if (res && res.success) {
                lastRenderSignature = '';
                fetchThread();
                loadThreads();
            } else {
                $pending.remove();
                $('#wchatText').val(text).trigger('input');
                toastr.error((res && res.error) || 'Message could not be sent.');
            }
        }).fail(function (xhr) {
            $('#wchatSend').prop('disabled', false);
            $pending.remove();
            // Hand the text back rather than losing what they typed.
            $('#wchatText').val(text).trigger('input');
            var msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) || 'Message could not be sent.';
            toastr.error(msg);
        });
    }

    function scrollToLatest() {
        var box = document.getElementById('wchatMsgs');
        if (box) box.scrollTop = box.scrollHeight;
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
    $('#wchatBack').on('click', function () { $('#wchatWrap').removeClass('chat-open'); });
    $('#wchatRefresh').on('click', function () { lastRenderSignature = ''; fetchThread(); loadThreads(); });
    $('#wchatJump').on('click', scrollToLatest);
    $('#wchatMsgs').on('scroll', function () {
        var box = this;
        $('#wchatJump').toggle(box.scrollHeight - box.scrollTop - box.clientHeight > 240);
    });
    // Esc backs out of a conversation on mobile, where the list is hidden behind it.
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') $('#wchatWrap').removeClass('chat-open');
    });
    $(document).on('click', '.wchat-filter', function () {
        filter = $(this).data('filter');
        $('.wchat-filter').removeClass('active');
        $(this).addClass('active');
        renderThreads(threads);
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
