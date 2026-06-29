@php
    $sp_basis = (isset($item) && $item) ? ($item->selling_price_basis ?? 'primary') : 'primary';
@endphp
<div class="col-md-3">
    <div class="form-group form-group-custom">
        <label class="custom-label">Prices are per</label>
        <select name="selling_price_basis" id="selling_price_basis"
            class="form-control selling_price_basis_select">
            <option value="primary" {{ $sp_basis === 'secondary' ? '' : 'selected' }}>base unit</option>
            <option value="secondary" {{ $sp_basis === 'secondary' ? 'selected' : '' }}>alternate unit</option>
        </select>
    </div>
</div>
<script>
    (function () {
        function start($) {
            function sync() {
                var $sel = $('#selling_price_basis');
                if (!$sel.length) return;
                var pu = ($('#primary_unit option:selected').text() || '').trim();
                var su = ($('#secondary_unit option:selected').text() || '').trim();
                $sel.find('option[value="primary"]').text(pu || 'base unit');
                var $alt = $sel.find('option[value="secondary"]');
                if (su) {
                    $alt.text(su).prop('disabled', false).prop('hidden', false);
                } else {
                    $alt.text('alternate unit').prop('disabled', true).prop('hidden', true);
                    if ($sel.val() === 'secondary') $sel.val('primary');
                }
            }
            // select2 fires change via jQuery .trigger(), so these must be jQuery-bound.
            $(document).on('change keyup', '#primary_unit, #secondary_unit, #primary_qty, #secondary_qty', sync);
            $(document).on('select2:select select2:unselect', '#primary_unit, #secondary_unit', sync);
            $(function () { sync(); });
            var n = 0, iv = setInterval(function () { sync(); if (++n > 20) clearInterval(iv); }, 400);
        }
        // This partial renders mid-page, before jQuery is loaded at the bottom — wait for it.
        var tries = 0;
        var wait = setInterval(function () {
            if (typeof jQuery !== 'undefined') { clearInterval(wait); start(jQuery); }
            else if (++tries > 100) { clearInterval(wait); }
        }, 100);
    })();
</script>
