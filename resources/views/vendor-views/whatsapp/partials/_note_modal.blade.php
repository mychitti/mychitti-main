{{-- Send a WhatsApp note to one customer.

     Module-agnostic on purpose: include it once per page, then open it from anywhere with
     waNote('9876543210', 'Ramesh'). The hospital's patient screen and every other module's
     customer screen share this one copy. --}}
<div class="modal fade" id="waNoteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="tio-chat"></i> {{ translate('Send a note') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-2" style="font-size:13px;">
                    <span class="text-muted">{{ translate('To') }}:</span>
                    <b id="waNoteTo">—</b>
                </div>

                <textarea id="waNoteText" class="form-control" rows="5" maxlength="2000"
                          placeholder="{{ translate('Type the note — advice after a consultation, an update on an order…') }}"></textarea>

                {{-- The template this falls back to is UTILITY. A vendor who sends offers through
                     it risks their own WhatsApp account, so the rule is stated where they type. --}}
                <div class="wa-note mt-2" style="font-size:12px;">
                    {{ translate('For advice and service messages only — not offers or promotions.') }}
                </div>

                <div id="waNoteQuote" class="mt-2" style="font-size:12px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="button" id="waNoteSend" class="btn btn-sm btn--primary">
                    <i class="tio-send"></i> {{ translate('Send') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var QUOTE_URL = '{{ route('vendor.whatsapp.note.quote') }}';
        var SEND_URL  = '{{ route('vendor.whatsapp.note.send') }}';
        var CSRF      = '{{ csrf_token() }}';
        var phone = '', name = '';

        window.waNote = function (customerPhone, customerName) {
            phone = customerPhone || '';
            name  = customerName || '';
            document.getElementById('waNoteTo').textContent = (name ? name + ' · ' : '') + phone;
            document.getElementById('waNoteText').value = '';
            document.getElementById('waNoteQuote').innerHTML = '<span class="text-muted">Checking…</span>';
            $('#waNoteModal').modal('show');

            // Price it before they type, not after they send.
            fetch(QUOTE_URL + '?phone=' + encodeURIComponent(phone), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (q) {
                    var colour = q.free ? '#1a7d4f' : (q.ready === false ? '#b42318' : '#64748b');
                    document.getElementById('waNoteQuote').innerHTML =
                        '<span style="color:' + colour + ';">' + (q.reason || '') + '</span>';
                })
                .catch(function () { document.getElementById('waNoteQuote').innerHTML = ''; });
        };

        document.getElementById('waNoteSend').addEventListener('click', function () {
            var btn  = this;
            var note = document.getElementById('waNoteText').value.trim();
            if (!note) { return; }

            btn.disabled = true;
            fetch(SEND_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ phone: phone, name: name, note: note })
            })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                .then(function (res) {
                    btn.disabled = false;
                    if (res.ok && res.d.success) {
                        $('#waNoteModal').modal('hide');
                        toastr.success(res.d.message || 'Note sent.');
                    } else {
                        toastr.error(res.d.message || 'Could not send that note.');
                    }
                })
                .catch(function () {
                    btn.disabled = false;
                    toastr.error('Could not send that note.');
                });
        });
    })();
</script>
