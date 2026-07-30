<style>
    /* Beats the inline display the show-on-website logic sets, so "this stock type has no
       variations" always wins over "this item could have them". */
    #add_variations_wrap.st-no-vars { display: none !important; }
</style>
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
    //
    // Variations follow the same logic. They only mean something for the two variation stock
    // types: on `simple` or `loose` there is one figure by definition, so the offer to add them
    // is withdrawn rather than left to contradict the setting. It is only hidden while the item
    // has none — an item that somehow already carries variations keeps its controls, because
    // taking them away is how you lose per-variation stock.
    (function () {
        function init() {
            var sel = document.getElementById('stock_type');
            var cb = document.getElementById('sell_loose_cb');
            var wrap = document.getElementById('sell_loose_wrap');
            var note = document.getElementById('sell_loose_note');
            if (!sel) return;

            var varWrap = document.getElementById('add_variations_wrap');
            var varCb = document.getElementById('add_variations_cb');
            // Set by the edit form when the item already has variations saved.
            var hasSaved = varWrap && varWrap.dataset.hasVariations === '1';

            // Hides through a class, never through inline style. The show-on-website logic owns
            // this wrapper's inline display, and two scripts assigning style.display would
            // overwrite each other depending on which ran last. A class with !important can only
            // ever hide — when variations ARE allowed this steps back and leaves the decision to
            // that other rule.
            function syncVariations(type) {
                if (!varWrap || !varCb || hasSaved) return;
                var allowed = (type === 'countable_variation' || type === 'measured');

                varWrap.classList.toggle('st-no-vars', !allowed);
                if (!allowed && varCb.checked) {
                    varCb.checked = false;
                    // Let whatever shows the Variations tab react to it closing.
                    varCb.dispatchEvent(new Event('change', { bubbles: true }));
                    if (window.jQuery) jQuery(varCb).trigger('change');
                }
            }

            function sync() {
                var type = sel.value;
                if (cb && wrap) {
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
                syncVariations(type);
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
