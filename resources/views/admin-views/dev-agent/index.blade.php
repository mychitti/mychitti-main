@extends('layouts.admin.app')

@section('title', 'Dev Agent')

@push('css_or_js')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
<style>
    #dev-chat-box {
        height: calc(100vh - 320px);
        min-height: 400px;
        overflow-y: auto;
        background: #0d1117;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .dev-msg { display: flex; gap: 12px; max-width: 100%; }
    .dev-msg.user { flex-direction: row-reverse; }
    .dev-avatar {
        width: 34px; height: 34px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; font-weight: 700; flex-shrink: 0;
    }
    .dev-avatar.ai  { background: #1f6feb; color: #fff; }
    .dev-avatar.usr { background: #3fb950; color: #fff; }
    .dev-bubble {
        background: #161b22;
        border: 1px solid #30363d;
        border-radius: 8px;
        padding: 12px 16px;
        color: #e6edf3;
        font-size: 14px;
        line-height: 1.6;
        max-width: calc(100% - 50px);
        word-break: break-word;
    }
    .dev-msg.user .dev-bubble {
        background: #1a4a2e;
        border-color: #3fb950;
        color: #e6edf3;
    }
    .dev-bubble pre {
        background: #0d1117 !important;
        border: 1px solid #30363d;
        border-radius: 6px;
        margin: 10px 0;
        overflow-x: auto;
    }
    .dev-bubble pre code { font-size: 13px; font-family: 'Fira Code', monospace; }
    .dev-bubble code:not(pre code) {
        background: #1f2428;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
        color: #79c0ff;
    }
    .dev-bubble p { margin: 0 0 8px; }
    .dev-bubble p:last-child { margin: 0; }
    .dev-bubble ul, .dev-bubble ol { padding-left: 20px; margin: 8px 0; }
    .dev-bubble h1,.dev-bubble h2,.dev-bubble h3,.dev-bubble h4 {
        color: #58a6ff; margin: 14px 0 6px; font-size: 15px;
    }
    .dev-bubble strong { color: #f0f6fc; }
    .dev-bubble hr { border-color: #30363d; margin: 10px 0; }
    #dev-input {
        background: #161b22;
        border: 1px solid #30363d;
        color: #e6edf3;
        border-radius: 8px;
        resize: none;
        font-family: 'Fira Code', monospace;
        font-size: 13px;
    }
    #dev-input:focus { border-color: #1f6feb; box-shadow: none; background: #161b22; color: #e6edf3; }
    #dev-input::placeholder { color: #6e7681; }
    .dev-toolbar { background: #161b22; border: 1px solid #30363d; border-radius: 8px; padding: 10px 14px; }
    .typing-dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #58a6ff; animation: blink 1s infinite; margin: 0 2px; }
    .typing-dot:nth-child(2) { animation-delay: .2s; }
    .typing-dot:nth-child(3) { animation-delay: .4s; }
    @keyframes blink { 0%,80%,100%{opacity:0} 40%{opacity:1} }
    .copy-btn {
        position: absolute; top: 6px; right: 6px;
        background: #21262d; border: 1px solid #30363d;
        color: #8b949e; border-radius: 4px; font-size: 11px;
        padding: 2px 8px; cursor: pointer;
    }
    .copy-btn:hover { background: #30363d; color: #e6edf3; }
    pre { position: relative; }
    .page-header-title span { color: #fff; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header" style="background:#0d1117; border-radius:8px; padding:16px 20px; margin-bottom:16px;">
        <div class="d-flex align-items-center justify-content-between">
            <h1 class="page-header-title mb-0" style="font-size:18px;">
                <i class="tio-code text-info mr-2"></i>
                <span style="color:#e6edf3;">Dev Agent</span>
                <span class="badge badge-soft-info ml-2" style="font-size:11px;">claude-sonnet-4-6</span>
            </h1>
            <div class="d-flex gap-2">
                <label for="dev-file-input" class="btn btn-sm mb-0" style="background:#21262d;border:1px solid #30363d;color:#8b949e;cursor:pointer;">
                    <i class="tio-attachment-diagonal"></i> Attach
                </label>
                <input type="file" id="dev-file-input" accept="image/*,.pdf" style="display:none">
                <button id="clear-btn" class="btn btn-sm" style="background:#21262d;border:1px solid #30363d;color:#8b949e;">
                    <i class="tio-clear-circle-outlined"></i> Clear
                </button>
            </div>
        </div>
        <div id="attach-preview" style="display:none; margin-top:8px;">
            <span class="badge" style="background:#1f6feb22;border:1px solid #1f6feb;color:#58a6ff;font-size:11px;" id="attach-name"></span>
            <button type="button" onclick="clearAttach()" style="background:none;border:none;color:#8b949e;font-size:14px;cursor:pointer;">×</button>
        </div>
    </div>

    <div id="dev-chat-box">
        <div class="dev-msg">
            <div class="dev-avatar ai">AI</div>
            <div class="dev-bubble">
                Hey! I'm your dev agent for the <strong>MyChitti</strong> project. I know the full stack — Laravel, Python RAG, AI service, nginx config, DB schema, deploy pipeline.<br><br>
                Give me a task and I'll write the complete code. What are we building?
            </div>
        </div>
    </div>

    <div class="dev-toolbar mt-3">
        <div class="d-flex gap-2 align-items-end">
            <textarea id="dev-input" class="form-control flex-fill"
                rows="3" placeholder="Describe the task... (Shift+Enter for new line, Enter to send)"></textarea>
            <button id="send-btn" class="btn btn-primary px-4" style="height:72px;min-width:80px;">
                <i class="tio-send"></i>
            </button>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
const chatBox  = document.getElementById('dev-chat-box');
const input    = document.getElementById('dev-input');
const sendBtn  = document.getElementById('send-btn');
const clearBtn = document.getElementById('clear-btn');
let attachFile = null;

// Configure marked
marked.setOptions({ breaks: true, gfm: true });

function renderMarkdown(text) {
    const html = marked.parse(text);
    return html;
}

function addMessage(role, content, isHtml = false) {
    const isUser = role === 'user';
    const div = document.createElement('div');
    div.className = 'dev-msg' + (isUser ? ' user' : '');
    const avatar = `<div class="dev-avatar ${isUser ? 'usr' : 'ai'}">${isUser ? 'YOU' : 'AI'}</div>`;
    const body   = isHtml ? content : renderMarkdown(content);
    div.innerHTML = `${avatar}<div class="dev-bubble">${body}</div>`;
    chatBox.appendChild(div);

    // Syntax highlight all code blocks
    div.querySelectorAll('pre code').forEach(block => {
        hljs.highlightElement(block);
        // Add copy button
        const pre = block.parentElement;
        const btn = document.createElement('button');
        btn.className = 'copy-btn';
        btn.textContent = 'copy';
        btn.onclick = () => {
            navigator.clipboard.writeText(block.innerText);
            btn.textContent = 'copied!';
            setTimeout(() => btn.textContent = 'copy', 2000);
        };
        pre.appendChild(btn);
    });

    chatBox.scrollTop = chatBox.scrollHeight;
    return div;
}

function addTyping() {
    const div = document.createElement('div');
    div.className = 'dev-msg';
    div.id = 'typing-indicator';
    div.innerHTML = `<div class="dev-avatar ai">AI</div><div class="dev-bubble"><span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span></div>`;
    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
}

function removeTyping() {
    const el = document.getElementById('typing-indicator');
    if (el) el.remove();
}

function setLoading(loading) {
    sendBtn.disabled  = loading;
    input.disabled    = loading;
    sendBtn.innerHTML = loading ? '<i class="tio-loading tio-spin"></i>' : '<i class="tio-send"></i>';
}

async function sendMessage() {
    const msg = input.value.trim();
    if (!msg && !attachFile) return;

    addMessage('user', msg || '[File attached]');
    input.value = '';
    addTyping();
    setLoading(true);

    const formData = new FormData();
    formData.append('message', msg);
    formData.append('_token', '{{ csrf_token() }}');
    if (attachFile) {
        formData.append('file', attachFile);
        clearAttach();
    }

    try {
        const res  = await fetch('{{ route("admin.dev-agent.chat") }}', { method: 'POST', body: formData });
        const data = await res.json();
        removeTyping();

        if (data.success) {
            addMessage('assistant', data.message);
        } else {
            addMessage('assistant', `**Error:** ${data.message || 'Something went wrong.'}`);
        }
    } catch (e) {
        removeTyping();
        addMessage('assistant', '**Network error.** Please try again.');
    }
    setLoading(false);
}

// Enter to send, Shift+Enter for new line
input.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});
sendBtn.addEventListener('click', sendMessage);

// Clear conversation
clearBtn.addEventListener('click', async () => {
    if (!confirm('Clear the conversation?')) return;
    await fetch('{{ route("admin.dev-agent.clear") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    });
    chatBox.innerHTML = '';
    addMessage('assistant', 'Conversation cleared. What are we building?');
});

// File attach
document.getElementById('dev-file-input').addEventListener('change', function() {
    attachFile = this.files[0];
    if (attachFile) {
        document.getElementById('attach-name').textContent = attachFile.name;
        document.getElementById('attach-preview').style.display = 'block';
    }
});

function clearAttach() {
    attachFile = null;
    document.getElementById('dev-file-input').value = '';
    document.getElementById('attach-preview').style.display = 'none';
}
</script>
@endpush
