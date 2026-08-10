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

        // Absent until the onboarding fee is paid — the button is a checkout form until then.
        var $connectBtn = document.getElementById('wa-connect-btn');
        if ($connectBtn) $connectBtn.addEventListener('click', function () {
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
