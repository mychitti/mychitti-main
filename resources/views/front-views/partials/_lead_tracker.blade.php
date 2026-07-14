{{-- Global lead-signal tracker (Phase 3 §3.3).
     Fires track-store-contact for lead actions so they land in lead_signals (Vendor Hub Lead Inbox).
     - Auto-captures WhatsApp link clicks.
     - Any element tagged data-lead-action="booking|quote|website|whatsapp|direction" is tracked;
       the store id is read from the nearest [data-lead-store] / [data-store-id], or window.mcStoreId.
     Call clicks are handled by store_webpage/partials/phone-actions.blade.php — no overlap here. --}}
@once
    <script> 
        (function () {
            var trackUrl = '{{ route('track.store.contact') }}';
            var tokenEl = document.querySelector('meta[name="csrf-token"]');
            var csrf = tokenEl ? tokenEl.getAttribute('content') : '';

            function track(storeId, action) {
                if (!storeId || !action) return;
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
            // Exposed so any inline handler can log a lead: mcTrackLead(storeId, 'booking')
            window.mcTrackLead = track;

            function storeIdFor(el) {
                var holder = el.closest('[data-lead-store],[data-store-id]');
                if (holder) {
                    return holder.getAttribute('data-lead-store') || holder.getAttribute('data-store-id');
                }
                return window.mcStoreId || '';
            }

            // Capture phase so it runs before the link navigates away.
            document.addEventListener('click', function (e) {
                var tagged = e.target.closest('[data-lead-action]');
                if (tagged) {
                    track(storeIdFor(tagged), tagged.getAttribute('data-lead-action'));
                    return;
                }
                var wa = e.target.closest(
                    'a[href*="wa.me"], a[href*="api.whatsapp.com"], a[href*="web.whatsapp.com"], a[href*="whatsapp://"]'
                );
                if (wa) {
                    track(storeIdFor(wa), 'whatsapp');
                }
            }, true);
        })();
    </script>
@endonce
