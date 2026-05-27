<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pusher Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: monospace; padding: 30px; background: #1a1a2e; color: #eee; }
        h2 { color: #e94560; }
        #log { background: #16213e; border: 1px solid #0f3460; padding: 15px; min-height: 200px; border-radius: 6px; }
        .entry { padding: 4px 0; border-bottom: 1px solid #0f3460; }
        .entry.ok { color: #4ecca3; }
        .entry.err { color: #e94560; }
        .entry.info { color: #aaa; }
        button { background: #e94560; color: #fff; border: none; padding: 10px 24px; cursor: pointer; border-radius: 4px; font-size: 15px; margin-top: 16px; }
        button:hover { background: #c73652; }
        input[type=text] { padding: 8px 12px; width: 340px; border-radius: 4px; border: 1px solid #0f3460; background: #16213e; color: #eee; font-size: 14px; }
        label { color: #aaa; font-size: 13px; }
    </style>
</head>
<body>
<h2>🔌 Laravel Pusher Test</h2>
<p style="color:#aaa; font-size:13px;">
    Host: <strong>{{ env('PUSHER_HOST') }}</strong> &nbsp;|&nbsp;
    Port: <strong>{{ env('PUSHER_PORT') }}</strong> &nbsp;|&nbsp;
    Key: <strong>{{ env('PUSHER_APP_KEY') }}</strong>
</p>

<div style="margin-bottom:10px">
    <label>Message to broadcast:</label><br>
    <input type="text" id="msg" value="Hello from Pusher!">
    <button onclick="triggerEvent()">Broadcast Event</button>
</div>

<p style="color:#aaa; font-size:13px;">Listening on channel <strong>test-channel</strong> for event <strong>test-event</strong></p>
<div id="log"><div class="entry info">— waiting for events —</div></div>

<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script>
    Pusher.logToConsole = true;

    const pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
        wsHost:           '{{ env('PUSHER_HOST') }}',
        wsPort:           {{ env('PUSHER_PORT', 443) }},
        wssPort:          {{ env('PUSHER_PORT', 443) }},
        forceTLS:         {{ env('PUSHER_SCHEME', 'https') === 'https' ? 'true' : 'false' }},
        disableStats:     true,
        enabledTransports: ['ws', 'wss'],
        cluster:          '{{ env('PUSHER_APP_CLUSTER', 'ap2') }}',
    });

    const channel = pusher.subscribe('test-channel');

    pusher.connection.bind('connected', () => log('✅ Connected to Pusher / Soketi', 'ok'));
    pusher.connection.bind('error',     (err) => log('❌ Connection error: ' + JSON.stringify(err), 'err'));
    pusher.connection.bind('disconnected', () => log('⚠️  Disconnected', 'err'));

    channel.bind('pusher:subscription_succeeded', () => log('📡 Subscribed to test-channel', 'ok'));
    channel.bind('pusher:subscription_error',     (err) => log('❌ Subscription error: ' + JSON.stringify(err), 'err'));

    channel.bind('test-event', (data) => {
        log('📨 Event received: ' + JSON.stringify(data), 'ok');
    });

    function log(msg, cls) {
        const el = document.getElementById('log');
        const d = document.createElement('div');
        d.className = 'entry ' + (cls || 'info');
        d.textContent = new Date().toLocaleTimeString() + '  ' + msg;
        el.appendChild(d);
        el.scrollTop = el.scrollHeight;
    }

    function triggerEvent() {
        const message = document.getElementById('msg').value;
        log('→ Sending broadcast...', 'info');
        fetch('{{ route('test.trigger') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify({ message }),
        })
        .then(r => r.json())
        .then(d => log('→ Server responded: ' + JSON.stringify(d), 'info'))
        .catch(e => log('❌ Fetch error: ' + e, 'err'));
    }
</script>
</body>
</html>
