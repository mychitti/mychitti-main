<script>
    // Keep Stock Type and the loose checkbox from disagreeing.
    //
    // They answer different questions — stock_type is where the stock lives, sell_loose is
    // whether a quantity may be a weighed decimal — but not every pairing is meaningful:
    //
    //   loose               already IS simple-plus-weighed, so the box is ticked and locked
    //   simple / countable  nothing is weighed; the box is cleared and hidden
    //   measured            left open on purpose — packs PLUS a custom weight is what lets the
    //                       counter sell 150 g when the packs are 100 g and 200 g
    //
    // The server applies the same rules on save (see applyLooseSelling), so a disabled box that
    // submits nothing still lands on the right value.
    (function () {
        function init() {
            var sel = document.getElementById('stock_type');
            var cb = document.getElementById('sell_loose_cb');
            var wrap = document.getElementById('sell_loose_wrap');
            var note = document.getElementById('sell_loose_note');
            if (!sel || !cb || !wrap) return;

            function sync() {
                var type = sel.value;
                if (type === 'measured') {
                    wrap.style.display = '';
                    cb.disabled = false;
                    if (note) note.textContent = 'Also sell loose — lets the counter weigh a custom quantity (e.g. 150 g) alongside the fixed packs';
                } else if (type === 'loose') {
                    wrap.style.display = '';
                    cb.checked = true;
                    cb.disabled = true;
                    if (note) note.textContent = 'Weighed at the time of sale — always on for this stock type';
                } else {
                    cb.checked = false;
                    cb.disabled = true;
                    wrap.style.display = 'none';
                }
            }

            sel.addEventListener('change', sync);
            if (window.jQuery) jQuery(sel).on('change', sync); // select2 fires only the jQuery event
            sync();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
