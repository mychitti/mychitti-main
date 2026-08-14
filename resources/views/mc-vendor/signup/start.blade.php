@extends('mc-vendor.theme.layout')

@section('title', 'List Your Business Free — MC Vendor Hub')
@section('meta_description', 'Get your business listed on My Chitti. Sign up in seconds with Google or your phone number, or fill the full listing form now.')

@section('styles')
    <style>
        .start-choice {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: stretch;
        }

        @media (max-width: 900px) {
            .start-choice {
                grid-template-columns: 1fr;
            }
        }

        .choice-card {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 32px 30px;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .choice-card.is-quick {
            border-color: var(--blue);
            box-shadow: 0 10px 30px rgba(21, 101, 192, 0.08);
        }

        .choice-badge {
            position: absolute;
            top: -11px;
            right: 24px;
            background: var(--green);
            color: var(--white);
            font-size: 11.5px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .choice-kicker {
            font-size: 12.5px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--ink-faint);
            margin-bottom: 10px;
        }

        .choice-card h2 {
            font-size: 22px;
            line-height: 1.25;
            margin-bottom: 10px;
        }

        .choice-card p.sub {
            font-size: 15px;
            color: var(--ink-soft);
            margin-bottom: 22px;
        }

        .choice-list {
            list-style: none;
            margin: 0 0 26px;
            padding: 0;
        }

        .choice-list li {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            font-size: 14.5px;
            color: var(--ink-soft);
            padding: 7px 0;
        }

        .choice-list li .tick {
            color: var(--green-dark);
            font-weight: 800;
            flex-shrink: 0;
        }

        .choice-foot {
            margin-top: auto;
        }

        .auth-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 13px 20px;
            border-radius: 10px;
            border: 1.5px solid var(--line);
            background: var(--white);
            color: var(--ink);
            font-family: 'Manrope', sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all .15s;
            margin-bottom: 12px;
        }

        .auth-btn:hover {
            border-color: var(--blue);
            background: var(--blue-pale);
        }

        .auth-btn svg {
            width: 19px;
            height: 19px;
            flex-shrink: 0;
        }

        .auth-sep {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 16px 0;
            color: var(--ink-faint);
            font-size: 12.5px;
            font-weight: 700;
        }

        .auth-sep::before,
        .auth-sep::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--line);
        }

        .choice-note {
            font-size: 13px;
            color: var(--ink-faint);
            margin-top: 14px;
        }

        .otp-panel {
            display: none;
        }

        .otp-panel.is-open {
            display: block;
        }

        .otp-panel label {
            display: block;
            font-size: 13.5px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .otp-panel input[type="tel"] {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid var(--line);
            border-radius: 10px;
            font-family: 'Manrope', sans-serif;
            font-size: 15px;
            color: var(--ink);
        }

        .otp-panel input[type="tel"]:focus {
            outline: none;
            border-color: var(--blue);
        }

        .otp-digits {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 14px;
        }

        .otp-digits input {
            width: 52px;
            height: 56px;
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            border: 1.5px solid var(--line);
            border-radius: 10px;
            font-family: 'Manrope', sans-serif;
            color: var(--ink);
        }

        .otp-digits input:focus {
            outline: none;
            border-color: var(--blue);
        }

        .otp-msg {
            font-size: 13.5px;
            font-weight: 600;
            margin-top: 10px;
            min-height: 18px;
        }

        .otp-msg.is-error {
            color: #C0392B;
        }

        .otp-msg.is-ok {
            color: var(--green-dark);
        }

        .otp-back {
            background: none;
            border: none;
            color: var(--ink-faint);
            font-family: 'Manrope', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            padding: 10px 0 0;
        }

        .otp-back:hover {
            color: var(--blue);
        }

        .start-reassure {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 28px;
            margin-top: 42px;
            font-size: 14px;
            color: var(--ink-soft);
        }

        .start-reassure span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
    </style>
@endsection

@section('content')

    <div class="wrap breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span>List Your Business</div>

    <section class="page-hero" style="padding-bottom:32px;">
        <div class="wrap" style="text-align:center;">
            <span class="eyebrow"><span class="dot"></span> Free Forever · No Card Required</span>
            <h1 style="max-width:760px; margin:0 auto 16px;">Get your business found on <span>My Chitti</span>.</h1>
            <p class="lede" style="margin:0 auto;">Pick how you want to start. You can always finish the rest later.</p>
        </div>
    </section>

    <section style="padding-bottom:96px;">
        <div class="wrap">
            <div class="start-choice">

                {{-- Existing full listing --}}
                <div class="choice-card">
                    <div class="choice-kicker">Full Listing</div>
                    <h2>Set up your complete profile now</h2>
                    <p class="sub">The full six-step form. Best if you have your GST or ID document handy and want to go live straight away.</p>

                    <ul class="choice-list">
                        <li><span class="tick">✓</span> Business name, address and map location</li>
                        <li><span class="tick">✓</span> Category and the services you offer</li>
                        <li><span class="tick">✓</span> Logo, cover photo and verification documents</li>
                        <li><span class="tick">✓</span> Goes to admin for approval as soon as you submit</li>
                    </ul>

                    <div class="choice-foot">
                        <a href="{{ route('new-store.create') }}" class="btn btn-primary" style="width:100%; justify-content:center;">
                            Continue to full form
                        </a>
                        <p class="choice-note">Takes about 10 minutes · Needs GST or ID proof</p>
                    </div>
                </div>

                {{-- Quick signup --}}
                <div class="choice-card is-quick">
                    <span class="choice-badge">Fastest</span>
                    <div class="choice-kicker">Quick Signup</div>
                    <h2>Claim your spot in 30 seconds</h2>
                    <p class="sub">Just verify who you are. We'll create your account right away and you can fill in the business details whenever you're ready.</p>

                    <div id="quickChooser">
                        @if (config('mychitti.google_signup_enabled'))
                            <a href="{{ route('quick-signup.google.redirect') }}" class="auth-btn">
                                <svg viewBox="0 0 48 48" aria-hidden="true">
                                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                                </svg>
                                Continue with Google
                            </a>

                            <div class="auth-sep">OR</div>
                        @endif

                        <button type="button" class="auth-btn" id="quickOtpBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="5" y="2" width="14" height="20" rx="2"></rect>
                                <line x1="12" y1="18" x2="12.01" y2="18"></line>
                            </svg>
                            Continue with OTP
                        </button>
                    </div>

                    {{-- Step 1: phone --}}
                    <div class="otp-panel" id="otpPhoneStep">
                        <label for="qsPhone">Your phone number</label>
                        <input type="tel" id="qsPhone" placeholder="e.g. 9988776655" autocomplete="tel">
                        <button type="button" class="btn btn-primary" id="qsSendOtp" style="width:100%; margin-top:12px;">
                            Send OTP
                        </button>
                        <p class="otp-msg" id="qsPhoneMsg" role="status" aria-live="polite"></p>
                        <button type="button" class="otp-back" data-back="chooser">← Back</button>
                    </div>

                    {{-- Step 2: code --}}
                    <div class="otp-panel" id="otpCodeStep">
                        <label style="text-align:center;">Enter the 4-digit code sent to <span id="qsPhoneEcho"></span></label>
                        <div class="otp-digits">
                            <input type="text" inputmode="numeric" maxlength="1" aria-label="OTP digit 1">
                            <input type="text" inputmode="numeric" maxlength="1" aria-label="OTP digit 2">
                            <input type="text" inputmode="numeric" maxlength="1" aria-label="OTP digit 3">
                            <input type="text" inputmode="numeric" maxlength="1" aria-label="OTP digit 4">
                        </div>
                        <button type="button" class="btn btn-primary" id="qsVerifyOtp" style="width:100%;">
                            Verify &amp; continue
                        </button>
                        <p class="otp-msg" id="qsCodeMsg" role="status" aria-live="polite"></p>
                        <button type="button" class="otp-back" data-back="phone">← Change number</button>
                    </div>

                    <div class="choice-foot">
                        <p class="choice-note">
                            No documents needed to start. Your listing stays private until you complete your profile and it's approved.
                        </p>
                    </div>
                </div>

            </div>

            <div class="start-reassure">
                <span>🆓 Free listing, forever</span>
                <span>🔒 Your details are never sold</span>
                <span>📞 Support on 9951968473</span>
            </div>
        </div>
    </section>

@endsection

@section('scripts')
    <script>
        (function () {
            var csrf     = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            var chooser  = document.getElementById('quickChooser');
            var phoneBox = document.getElementById('otpPhoneStep');
            var codeBox  = document.getElementById('otpCodeStep');
            var phoneIn  = document.getElementById('qsPhone');
            var phoneMsg = document.getElementById('qsPhoneMsg');
            var codeMsg  = document.getElementById('qsCodeMsg');
            var sendBtn  = document.getElementById('qsSendOtp');
            var verifyBtn = document.getElementById('qsVerifyOtp');
            var digits   = Array.prototype.slice.call(codeBox.querySelectorAll('input'));

            function show(step) {
                chooser.style.display = step === 'chooser' ? '' : 'none';
                phoneBox.classList.toggle('is-open', step === 'phone');
                codeBox.classList.toggle('is-open', step === 'code');
            }

            function say(el, text, ok) {
                el.textContent = text;
                el.classList.toggle('is-error', !ok && !!text);
                el.classList.toggle('is-ok', !!ok);
            }

            function post(url, body) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify(body)
                }).then(function (r) { return r.json(); });
            }

            document.getElementById('quickOtpBtn').addEventListener('click', function () {
                show('phone');
                phoneIn.focus();
            });

            document.querySelectorAll('[data-back]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    say(phoneMsg, ''); say(codeMsg, '');
                    show(btn.dataset.back);
                });
            });

            sendBtn.addEventListener('click', function () {
                var phone = phoneIn.value.trim();
                if (phone.length < 10) {
                    say(phoneMsg, 'Enter a valid phone number.');
                    return;
                }
                sendBtn.disabled = true;
                say(phoneMsg, 'Sending…', true);

                post('{{ route('quick-signup.send-otp') }}', { phone: phone })
                    .then(function (d) {
                        sendBtn.disabled = false;
                        if (!d.status) { say(phoneMsg, d.message); return; }
                        document.getElementById('qsPhoneEcho').textContent = phone;
                        say(phoneMsg, '');
                        show('code');
                        digits[0].focus();
                    })
                    .catch(function () {
                        sendBtn.disabled = false;
                        say(phoneMsg, 'Could not send the OTP. Please try again.');
                    });
            });

            digits.forEach(function (input, i) {
                input.addEventListener('input', function () {
                    input.value = input.value.replace(/\D/g, '');
                    if (input.value && i < digits.length - 1) digits[i + 1].focus();
                });
                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Backspace' && !input.value && i > 0) digits[i - 1].focus();
                });
            });

            verifyBtn.addEventListener('click', function () {
                var otp = digits.map(function (d) { return d.value; }).join('');
                if (otp.length < 4) {
                    say(codeMsg, 'Enter all four digits.');
                    return;
                }
                verifyBtn.disabled = true;
                say(codeMsg, 'Verifying…', true);

                post('{{ route('quick-signup.verify-otp') }}', {
                    phone: phoneIn.value.trim(),
                    otp: otp
                })
                    .then(function (d) {
                        if (d.status && d.redirect) { window.location.href = d.redirect; return; }
                        verifyBtn.disabled = false;
                        say(codeMsg, d.message || 'Incorrect or expired OTP.');
                        digits.forEach(function (x) { x.value = ''; });
                        digits[0].focus();
                    })
                    .catch(function () {
                        verifyBtn.disabled = false;
                        say(codeMsg, 'Could not verify right now. Please try again.');
                    });
            });
        })();
    </script>
@endsection
