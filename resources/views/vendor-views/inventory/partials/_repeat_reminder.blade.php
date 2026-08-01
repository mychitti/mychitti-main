{{-- Per-item repeat purchase reminder.

     Two decisions, both the vendor's, and made per item — nothing is inherited from the category.
     Whether this item is chased at all, and how long after a sale it comes due. Rice is a month;
     a toothbrush is three; a washing machine is never.

     The hidden input is what tells the server the form carries this control at all — an unchecked
     checkbox submits nothing on its own, and "nothing" has to keep meaning "leave the saved value
     alone" for any other path that saves an item. --}}
@php
    $rrDays = isset($repeatDays) ? (int) $repeatDays : 0;
@endphp
<div class="col-12 mt-2">
    <div class="badge badge-soft-warning p-2 d-block text-left" style="white-space: normal;">
        <input type="hidden" name="repeat_reminder" value="0">
        <label class="custom-label cursor-pointer mb-0 d-flex align-items-center">
            <input type="checkbox" id="repeat_reminder_cb" name="repeat_reminder" value="1"
                {{ $rrDays > 0 ? 'checked' : '' }} class="form-check-input position-static ml-0 mr-2">
            <span>Remind the customer to buy this again</span>
        </label>
        <div id="repeat_days_wrap" class="d-flex align-items-center mt-2 {{ $rrDays > 0 ? '' : 'd-none' }}">
            <input type="number" name="repeat_days" id="repeat_days" min="1" max="730" step="1"
                value="{{ $rrDays > 0 ? $rrDays : 30 }}" class="form-control form-control-sm mr-2"
                style="width: 90px;">
            <span>days after they bought it</span>
        </div>
        {{-- Opens in a new tab on purpose: this sits inside an unsaved item form, and sending the
             vendor away to flip a switch would throw away everything they have typed. --}}
        <small class="d-block mt-1 text-muted">One WhatsApp message per customer listing everything
            that's due, at most once a fortnight. Switch the feature on under
            <a href="{{ route('vendor.notification-settings', ['direction' => 'send', 'tab' => 'whatsapp']) }}"
                target="_blank" rel="noopener">Notification Settings</a>.</small>
    </div>
</div>
<script>
    (function () {
        function init() {
            var cb = document.getElementById('repeat_reminder_cb');
            var wrap = document.getElementById('repeat_days_wrap');
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
