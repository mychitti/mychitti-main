{{-- Recall interval for this doctor.

     How long after a completed visit a patient is invited back, when they have nothing booked.
     It sits on the doctor rather than the hospital because the interval is a clinical judgement —
     a dermatologist's recall is not a dentist's — and left blank the doctor's patients are never
     chased, which is the right default for the specialities where recall is not a thing.

     The hidden input is what tells the server the form carries this control, so a save from any
     other path leaves the saved value alone rather than clearing it. --}}
@php
    $rbDays = isset($rebookDays) ? (int) $rebookDays : 0;
@endphp
{{-- Full width and visually contained, rather than a column in the grid above.

     As a grid cell this stranded itself: the Services multiselect makes that row tall, so
     anything after it lands far down the page with empty space either side, and unticked it was
     a lone checkbox trailing three lines of wrapped help. A panel of its own reads as a
     deliberate setting, and keeps the control and its explanation on one line each. --}}
<div class="col-12">
    <div class="border rounded px-3 py-3" style="background:#fbfcfe;">
        <div class="d-flex flex-wrap align-items-center" style="gap:14px;">
            <label class="mb-0 d-flex align-items-center" style="gap:8px; cursor:pointer;">
                <input type="hidden" name="rebook_reminder" value="0">
                <input type="checkbox" id="rebook_reminder_cb" name="rebook_reminder" value="1"
                    {{ $rbDays > 0 ? 'checked' : '' }} class="form-check-input position-static m-0">
                <span class="font-weight-bold">Invite patients back for a check-up</span>
            </label>

            <div id="rebook_days_wrap" class="input-group input-group-sm {{ $rbDays > 0 ? '' : 'd-none' }}"
                style="max-width:200px;">
                <input type="number" name="rebook_days" id="rebook_days" class="form-control" min="1" max="730"
                    step="1" value="{{ $rbDays > 0 ? $rbDays : 180 }}">
                <div class="input-group-append">
                    <span class="input-group-text">days later</span>
                </div>
            </div>
        </div>

        <small class="text-muted d-block mt-2">
            Counted from their last completed visit, and skips anyone who already has an appointment booked.
            Switch the feature on under
            <a href="{{ route('vendor.notification-settings', ['direction' => 'send', 'tab' => 'whatsapp']) }}"
                target="_blank" rel="noopener">Notification Settings</a>.
        </small>
    </div>
</div>
<script>
    (function () {
        function init() {
            var cb = document.getElementById('rebook_reminder_cb');
            var wrap = document.getElementById('rebook_days_wrap');
            if (!cb || !wrap) return;
            cb.addEventListener('change', function () {
                wrap.classList.toggle('d-none', !cb.checked);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
