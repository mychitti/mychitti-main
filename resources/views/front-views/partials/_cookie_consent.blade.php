{{-- DPDP Act 2023 — cookie/consent notice. Non-blocking notice shown once until acknowledged;
     records consent + timestamp in localStorage so it isn't shown again. --}}
<div id="mc-consent" class="mc-consent" role="dialog" aria-live="polite" aria-label="Privacy notice" hidden>
    <div class="mc-consent-text">
        We use cookies and your location to show relevant local services and improve My Chitti.
        By continuing you agree to our
        <a href="{{ route('privacy-policy') }}">Privacy Policy</a>.
    </div>
    <div class="mc-consent-actions">
        <a href="{{ route('privacy-policy') }}" class="mc-consent-link">Learn more</a>
        <button type="button" id="mc-consent-accept" class="mc-consent-btn">Accept</button>
    </div>
</div>

<style>
    .mc-consent { position: fixed; left: 16px; right: 16px; bottom: 16px; z-index: 11000;
        max-width: 720px; margin: 0 auto; display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        background: #1f2733; color: #e8ecf2; border: 1px solid rgba(255,255,255,.12); border-radius: 14px;
        padding: 14px 18px; box-shadow: 0 12px 34px rgba(0,0,0,.28); font-size: 13.5px; line-height: 1.5; }
    .mc-consent-text { flex: 1 1 300px; }
    .mc-consent-text a { color: #ffb59b; text-decoration: underline; }
    .mc-consent-actions { display: flex; align-items: center; gap: 12px; }
    .mc-consent-link { color: #cdd6e4; font-size: 13px; text-decoration: none; }
    .mc-consent-link:hover { color: #fff; text-decoration: underline; }
    .mc-consent-btn { background: var(--color-primary, #C8522A); color: #fff; border: 0; font-weight: 700;
        font-size: 13.5px; padding: 9px 22px; border-radius: 9px; cursor: pointer; }
    .mc-consent-btn:hover { filter: brightness(1.06); }
    @media (max-width: 520px) { .mc-consent { flex-direction: column; align-items: stretch; text-align: center; }
        .mc-consent-actions { justify-content: center; } }
</style>

<script>
    (function () {
        var KEY = 'mc_cookie_consent';
        var el = document.getElementById('mc-consent');
        if (!el) return;
        try { if (localStorage.getItem(KEY)) return; } catch (e) { return; }
        el.hidden = false;
        document.getElementById('mc-consent-accept').addEventListener('click', function () {
            try { localStorage.setItem(KEY, JSON.stringify({ v: 1, at: new Date().toISOString() })); } catch (e) {}
            el.hidden = true;
        });
    })();
</script>
