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
        {{-- Vendors routinely enter a bag purchase price against a per-kg selling price, which
             makes the item look like it costs twenty times what it does. Kept to one line so it
             cannot push the price fields out of the row — the full explanation rides the tooltip,
             and each price box carries its own "(per kg)" label. --}}
        <small class="form-text text-muted text-truncate" style="font-size:11px;" id="sp-basis-hint"
            data-toggle="tooltip" data-placement="bottom" title=""></small>
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

                // Name the chosen unit on each price box, so "1290" cannot be read as a bag
                // price while the field is actually asking for a kilo price.
                var unit = ($sel.val() === 'secondary' ? su : pu) || '';
                $('input[name="main_selling_price"], input[name="main_mrp"], input[name="main_landing_price"]')
                    .each(function () {
                        var $label = $(this).closest('.form-group').find('label').first();
                        if (!$label.length) return;
                        var $hint = $label.find('.sp-unit-hint');
                        if (!$hint.length) {
                            $hint = $('<span class="sp-unit-hint text-muted" style="font-weight:400;"></span>');
                            $label.append($hint);
                        }
                        $hint.text(unit ? ' (per ' + unit + ')' : '');
                    });

                // One visible line, full guidance on hover. Buying by the bag and selling by the
                // kilo is the common case, and it is only expressible once the pack size is on
                // record — so the tooltip points at that rather than at the dropdown they are
                // already looking at.
                var $hintLine = $('#sp-basis-hint');
                if ($hintLine.length) {
                    var u = unit || 'this unit';
                    $hintLine.html('All three prices must be per <b>' + u + '</b>.');
                    var tip;
                    if (!su) {
                        tip = 'Buying in bags or boxes but selling loose? Add that pack as an alternate unit under '
                            + 'Multi-UOM Setup below (e.g. 25 ' + (pu || 'kg') + ' = 1 bag), then you can price per bag here.';
                    } else if ($sel.val() === 'secondary') {
                        // Already on the alternate unit — the remaining trap is a 1 = 1 conversion,
                        // which divides by one and quietly stores the bag price as the kg price.
                        var pq = parseFloat($('#primary_qty').val()) || 0;
                        var sq = parseFloat($('#secondary_qty').val()) || 0;
                        tip = 'Enter Landing Price, Selling Price and MRP as ' + su + ' prices — they are converted to '
                            + 'per ' + pu + ' using your Multi-UOM conversion.'
                            + ((pq > 0 && sq > 0 && pq / sq !== 1) ? '' :
                                ' Set that conversion first (e.g. 25 ' + pu + ' = 1 ' + su + '), or the '
                                + su + ' price is stored as the ' + pu + ' price.');
                    } else {
                        tip = 'Buying per ' + su + ' but selling per ' + pu + '? Either enter Landing Price, Selling '
                            + 'Price and MRP all per ' + pu + ', or choose ' + su + ' here and enter all three per ' + su + '.';
                    }
                    $hintLine.attr('title', tip);
                    if ($hintLine.tooltip) {
                        $hintLine.tooltip('dispose').tooltip();
                    }
                }
            }
            // select2 fires change via jQuery .trigger(), so these must be jQuery-bound.
            // The basis dropdown itself has to re-run this, or the "per <unit>" line and the
            // price labels keep naming the unit that was selected when the page loaded.
            $(document).on('change keyup', '#selling_price_basis, #primary_unit, #secondary_unit, #primary_qty, #secondary_qty', sync);
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
