@php
    $__phoneList = (isset($phones) && !empty($phones))
        ? (array) $phones
        : ((isset($store['phone']) && $store['phone']) ? [$store['phone']] : []);
    $__storeId = $store['id'] ?? '';
@endphp
@foreach ($__phoneList as $__i => $__ph)
    @php $__ph = trim((string) $__ph); @endphp
    @if ($__ph !== '')
@if ($__i > 0), @endif<span class="store-phone-action" style="display:inline-flex;align-items:center;gap:6px;vertical-align:middle;">
            <a href="tel:{{ $__ph }}" class="store-phone-call" data-store-id="{{ $__storeId }}" title="{{ translate('Call') }} {{ $__ph }}">{{ $__ph }}</a>
            <button type="button" class="store-phone-copy" data-phone="{{ $__ph }}" data-store-id="{{ $__storeId }}" title="{{ translate('Copy number') }}" aria-label="{{ translate('Copy phone number') }}" style="border:0;background:transparent;padding:0;cursor:pointer;color:inherit;font-size:.85em;line-height:1;">
                <i class="fa fa-copy"></i>
            </button>
        </span>
    @endif
@endforeach
@once
    <script>
        (function () {
            var trackUrl = '{{ route('track.store.contact') }}';
            var tokenEl = document.querySelector('meta[name="csrf-token"]');
            var csrf = tokenEl ? tokenEl.getAttribute('content') : '';
            var shareStoreId = '{{ $store['id'] ?? '' }}';

            function trackContact(storeId, action) {
                if (!storeId) return;
                try {
                    var fd = new FormData();
                    fd.append('store_id', storeId);
                    fd.append('action', action);
                    fd.append('_token', csrf);
                    if (navigator.sendBeacon) {
                        navigator.sendBeacon(trackUrl, fd);
                    } else {
                        fetch(trackUrl, { method: 'POST', body: fd, keepalive: true });
                    }
                } catch (e) {}
            }

            document.addEventListener('click', function (e) {
                var copyBtn = e.target.closest('.store-phone-copy');
                if (copyBtn) {
                    e.preventDefault();
                    var num = copyBtn.getAttribute('data-phone') || '';
                    var mark = function () {
                        var ic = copyBtn.querySelector('i');
                        if (!ic) return;
                        var prev = ic.className;
                        ic.className = 'fa fa-check';
                        setTimeout(function () { ic.className = prev; }, 1500);
                    };
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(num).then(mark).catch(function () {});
                    } else {
                        var t = document.createElement('textarea');
                        t.value = num; t.style.position = 'fixed'; t.style.opacity = '0';
                        document.body.appendChild(t); t.focus(); t.select();
                        try { document.execCommand('copy'); mark(); } catch (err) {}
                        document.body.removeChild(t);
                    }
                    trackContact(copyBtn.getAttribute('data-store-id'), 'copy');
                    return;
                }

                var callLink = e.target.closest('.store-phone-call');
                if (callLink) {
                    trackContact(callLink.getAttribute('data-store-id'), 'call');
                    // do not preventDefault — let the tel: link proceed
                    return;
                }

                // ShareThis share buttons (.st-btn) — count each share click
                var shareBtn = e.target.closest('.st-btn');
                if (shareBtn) {
                    trackContact(shareStoreId, 'share');
                }
            });
        })();
    </script>
@endonce
