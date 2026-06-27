{{-- Reusable delete confirmation with an optional inventory-stock adjustment.
     Trigger with a button/link:
       <a class="inv-delete-btn" href="javascript:;"
          data-action="{{ route('...') }}"
          data-stock-label="Add this stock back to inventory">   {{-- omit data-stock-label to hide the checkbox --}}
--}}
<div class="modal fade" id="invDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="post" id="invDeleteForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to delete this record? This action cannot be undone.</p>
                    <div class="form-check" id="invDeleteStockWrap" style="display:none;">
                        <input type="checkbox" class="form-check-input" name="restock" value="1"
                            id="invDeleteRestock">
                        <label class="form-check-label" for="invDeleteRestock" id="invDeleteStockLabel"></label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    (function () {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest ? e.target.closest('.inv-delete-btn') : null;
            if (!btn) return;
            e.preventDefault();
            var form = document.getElementById('invDeleteForm');
            if (!form) return;
            form.setAttribute('action', btn.getAttribute('data-action'));
            var wrap = document.getElementById('invDeleteStockWrap');
            var cb = document.getElementById('invDeleteRestock');
            var lbl = document.getElementById('invDeleteStockLabel');
            var stockText = btn.getAttribute('data-stock-label');
            if (cb) cb.checked = false;
            if (stockText && wrap && lbl) {
                lbl.textContent = stockText;
                wrap.style.display = '';
            } else if (wrap) {
                wrap.style.display = 'none';
            }
            if (window.jQuery) {
                window.jQuery('#invDeleteModal').modal('show');
            }
        });
    })();
</script>
