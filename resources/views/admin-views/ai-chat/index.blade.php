 @extends('layouts.admin.app')

@section('title', 'AI Chat')

@section('content')
    <div class="content container-fluid">

        <div class="page-header">
            <h4 class="page-title">AI Assistant</h4>
        </div>

        <div class="card">
            <div class="card-body">

                {{-- One assistant per brief. Each keeps its own thread — switching reloads that
                     persona's history rather than continuing the last one's conversation. --}}
                <ul class="nav nav-pills mb-3" id="persona-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="javascript:;" data-persona="">
                            General
                        </a>
                    </li>
                    {{-- tabs(), not PERSONAS: the name and blurb are whatever the admin saved on
                         the assistant's row under AI Agents, falling back to the wording in code. --}}
                    @foreach (\App\Services\AdminAiPersona::tabs() as $key => $meta)
                        <li class="nav-item">
                            <a class="nav-link" href="javascript:;" data-persona="{{ $key }}"
                               title="{{ $meta['blurb'] }}">
                                <i class="{{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p class="text-muted mb-3" id="persona-blurb" style="font-size:12px;">
                    Ask anything about the platform.
                </p>


                <div id="chat-box"
                    style="height:420px;overflow-y:auto;border:1px solid #e5e5e5;padding:15px;border-radius:6px;margin-bottom:15px;background:#fafafa">
                    <div class="text-muted text-center">Loading chat…</div> 
                </div>

                <form id="chat-form" enctype="multipart/form-data">
                    @csrf

                    <div class="input-group mb-2">
                        <textarea class="form-control" id="message" name="message" placeholder="Type your message..." rows="2"></textarea>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">Send</button>
                        </div>
                    </div>

                    <small class="text-muted d-block mb-2" style="font-size:11px;">
                        AI can make mistakes. Check important information before acting on it.
                    </small>

                    <div id="attach-preview" style="display:none;flex-wrap:wrap;gap:4px;margin-bottom:8px"></div>

                    <div class="d-flex align-items-center gap-2">
                        <input type="file" id="fileInput" name="file" accept="image/*,.pdf" style="display:none">
                        <button type="button" class="btn btn-sm btn-secondary" id="attachFileBtn">
                            📎 Attach
                        </button>
                        <button type="button" class="btn btn-sm btn-info" id="startRecord">
                            🎤 Record
                        </button>
                        <button type="button" class="btn btn-sm btn-danger d-none" id="stopRecord">
                            ⏹ Stop
                        </button>
                    </div>

                </form>

                <div class="mt-3 text-right">
                    <button class="btn btn-sm btn-danger" id="clear-memory">
                        Clear memory
                    </button>
                </div>

            </div>
        </div>

    </div>
@endsection

@push('css_or_js')
<style>
    .ai-message-body { font-size: 0.92rem; line-height: 1.6; }
    .ai-message-body p { margin-bottom: 0.5rem; }
    .ai-message-body strong { font-weight: 700; }
    .ai-message-body em { font-style: italic; }
    .ai-message-body ul, .ai-message-body ol { padding-left: 1.4rem; margin-bottom: 0.5rem; }
    .ai-message-body li { margin-bottom: 0.2rem; }
    .ai-message-body h1, .ai-message-body h2, .ai-message-body h3 { font-weight: 700; margin: 0.6rem 0 0.3rem; }
    .ai-message-body code { background: #f0f0f0; padding: 1px 5px; border-radius: 3px; font-size: 0.85em; }
    .ai-message-body pre { background: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; }
    .ai-message-body a { color: #007bff; text-decoration: underline; }
</style>
@endpush

@push('script_2')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
let mediaRecorder;
let audioChunks = [];
let recordedBlob = null;

function showChip(id, icon, label, styles, onRemove) {
    $('#attach-preview').css('display', 'flex');
    var chip = $('<span id="' + id + '" style="display:inline-flex;align-items:center;gap:4px;border-radius:12px;padding:3px 10px;font-size:12px;' + styles + '"></span>');
    chip.append(icon + ' ' + label);
    var x = $('<button type="button" style="background:none;border:none;padding:0 0 0 4px;cursor:pointer;font-size:15px;line-height:1;opacity:.7">&times;</button>');
    x.on('click', function() { chip.remove(); if (!$('#attach-preview').children().length) $('#attach-preview').hide(); onRemove(); });
    chip.append(x);
    $('#attach-preview').append(chip);
}

/* file picker */
$('#attachFileBtn').on('click', function () {
    $('#fileInput').click();
});

$('#fileInput').on('change', function () {
    $('#file-chip').remove();
    if (!$('#attach-preview').children().length) $('#attach-preview').hide();
    var file = this.files[0];
    if (!file) return;
    var icon = file.type.startsWith('image/') ? '🖼️' : '📄';
    showChip('file-chip', icon, file.name, 'background:#e8f0fe;border:1px solid #c5d8fc;color:#1a56a0', function () {
        $('#fileInput').val('');
    });
});

/* voice recording */
$('#startRecord').on('click', async function () {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Voice recording not supported in this browser.');
        return;
    }
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    audioChunks = [];
    recordedBlob = null;
    $('#voice-chip').remove();
    if (!$('#attach-preview').children().length) $('#attach-preview').hide();

    mediaRecorder = new MediaRecorder(stream);
    mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
    mediaRecorder.onstop = () => {
        recordedBlob = new Blob(audioChunks, { type: 'audio/webm' });
        stream.getTracks().forEach(t => t.stop());
        $('#recording-chip').remove();
        showChip('voice-chip', '🎤', 'Voice ready', 'background:#e8fde8;border:1px solid #9fd99f;color:#2d6a2d', function () {
            recordedBlob = null;
        });
    };
    mediaRecorder.start();

    $('#startRecord').addClass('d-none');
    $('#stopRecord').removeClass('d-none');
    showChip('recording-chip', '🔴', 'Recording...', 'background:#fde8e8;border:1px solid #f9a0a0;color:#8b0000', function () {});
});

$('#stopRecord').on('click', function () {
    mediaRecorder.stop();
    $('#stopRecord').addClass('d-none');
    $('#startRecord').removeClass('d-none');
    $('#recording-chip').remove();
    if (!$('#attach-preview').children().length) $('#attach-preview').hide();
});

/* submit */
// Enter sends, Shift+Enter starts a new line. Ignored while an IME is composing, or picking a
// character in a non-Latin keyboard would send the half-typed word instead of choosing it.
$('#message').off('keydown').on('keydown', function(e){
    if (e.key !== 'Enter' || e.shiftKey || e.originalEvent?.isComposing) return;
    e.preventDefault();
    $('#chat-form').submit();
});

$('#chat-form').off('submit').on('submit', function(e){
    e.preventDefault();

    let formData = new FormData();
    let message = $('#message').val();

    formData.append('_token', "{{ csrf_token() }}");
    formData.append('message', message);
    formData.append('persona', activePersona);

    const file = $('#fileInput')[0].files[0];
    if (file) formData.append('file', file);
    if (recordedBlob) formData.append('voice', recordedBlob, 'voice.webm');

    if (!message && !file && !recordedBlob) return;

    if (message) renderMessage('user', message);
    else if (file) renderMessage('user', '[File sent]');
    else if (recordedBlob) renderMessage('user', '[Voice message sent]');

    $('#message').val('');
    $('#fileInput').val('');
    recordedBlob = null;
    $('#attach-preview').empty().hide();

    $.ajax({
        url: "{{ route('admin.ai-chat.send') }}",
        type: "POST", 
        data: formData,
        processData: false,
        contentType: false,
        success: function(res){
            if (res.success) {
                renderMessage('assistant', res.message);
                if (res.audio_url) {
                    var audio = new Audio(res.audio_url);
                    audio.play();
                }
            } else {
                alert(res.message || 'Something went wrong.');
            }
        },
        error: function(){
            alert('Upload failed.');
        }
    });
});
</script>

<script>
    const chatBox = $('#chat-box');

    var adminCurrentAudio = null;

    function renderMessage(role, content, time = null) {
        let isUser = role === 'user';
        let align  = isUser ? 'text-right' : 'text-left';
        let label  = isUser ? 'You' : 'AI';
        let listenBtn = !isUser ? `<button class="btn btn-sm btn-link p-0 ml-1 admin-tts-btn" style="font-size:12px;opacity:0.6;" title="Listen">&#128264;</button>` : '';
        let body   = isUser
            ? `<span class="badge badge-primary" style="white-space:pre-wrap;font-size:0.9rem;">${$('<div>').text(content).html()}</span>`
            : `<div class="ai-message-body" style="text-align:left;">${marked.parse(content)}</div>`;

        let html = `
           <div class="mb-3 ${align}" ${!isUser ? 'data-text="' + $('<div>').text(content).html() + '"' : ''}>
               ${body}
               <div class="text-muted" style="font-size:0.75rem;margin-top:2px;">${label} ${listenBtn}</div>
           </div>
       `;

        chatBox.append(html);
        chatBox.scrollTop(chatBox[0].scrollHeight);
    }

    $(document).on('click', '.admin-tts-btn', function() {
        var $btn = $(this);
        var $msg = $btn.closest('[data-text]');
        var text = $msg.attr('data-text');
        if (!text) return;

        if (adminCurrentAudio && !adminCurrentAudio.paused) {
            adminCurrentAudio.pause();
            adminCurrentAudio = null;
            $('.admin-tts-btn').html('&#128264;').css('opacity', '0.6');
            return;
        }

        var plainText = text.replace(/[#*_`~\[\]()>|\\-]/g, '').substring(0, 4096);
        $btn.html('&#9203;').css('opacity', '1');

        $.post({
            url: "{{ route('admin.ai-chat.tts') }}",
            data: { _token: "{{ csrf_token() }}", text: plainText },
            success: function(res) {
                if (res.success && res.audio_url) {
                    adminCurrentAudio = new Audio(res.audio_url);
                    adminCurrentAudio.play();
                    $btn.html('&#9209;');
                    adminCurrentAudio.onended = function() {
                        $btn.html('&#128264;').css('opacity', '0.6');
                        adminCurrentAudio = null;
                    };
                } else {
                    $btn.html('&#128264;').css('opacity', '0.6');
                }
            },
            error: function() {
                $btn.html('&#128264;').css('opacity', '0.6');
            }
        });
    });

    // Which assistant is being talked to. Sent with every call so the reply, the thread it is
    // stored in and the memory it is cleared from all refer to the same one.
    var activePersona = '';
    var personaBlurbs = @json(collect(\App\Services\AdminAiPersona::tabs())->map(fn($m) => $m['blurb']));

    $('#persona-tabs').on('click', 'a[data-persona]', function() {
        var next = $(this).data('persona') || '';
        if (next === activePersona) return;

        activePersona = next;
        $('#persona-tabs .nav-link').removeClass('active');
        $(this).addClass('active');
        $('#persona-blurb').text(personaBlurbs[activePersona] || 'Ask anything about the platform.');
        loadHistory();
    });

    function loadHistory() {
        chatBox.html('<div class="text-muted text-center">Loading chat…</div>');
        $.get("{{ route('admin.ai-chat.history') }}", { persona: activePersona }, function(res) {
            chatBox.html('');
            if (res.success && res.messages.length) {
                res.messages.forEach(function(row) {
                    renderMessage(row.role, row.content, row.created_at);
                });
            } else {
                chatBox.html('<div class="text-muted text-center">No conversation yet.</div>');
            }
        });
    }

    loadHistory();

    $('#clear-memory').on('click', function() {
        var which = activePersona ? (personaBlurbs[activePersona] ? $('#persona-tabs .nav-link.active').text().trim() : 'this assistant') : 'the general assistant';
        if (!confirm('Clear the memory of ' + which + '? The other assistants keep theirs.')) return;
        $.post("{{ route('admin.ai-chat.clear') }}", {
            _token: "{{ csrf_token() }}",
            persona: activePersona
        }, function(res) {
            if (res.success) {
                chatBox.html('<div class="text-muted text-center">Memory cleared.</div>');
            }
        });
    });
</script>
@endpush
