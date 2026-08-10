{{-- Bulk delete controls for an inventory report page.

     "Delete Selected" acts on the ticked rows; "Delete All" acts on every row the current
     filters produce, resolved server-side from the same query the page was rendered with —
     not from the checkboxes, so it stays correct on a paginated report and never has to post
     thousands of ids past max_input_vars.

     @include('vendor-views.inventory.report._bulk_delete', [
         'scope'          => 'sale',                                             // unique per toolbar on the page
         'deleteRoute'    => route('vendor.inventory.report.sale-bulk-delete'),
         'selectedText'   => 'the selected sale invoices',
         'allText'        => 'every sale invoice in this report',
         'checkboxClass'  => 'check_select',                                     // optional, default check_select
         'checkAllId'     => 'check_all',                                        // optional, reuse an existing Select All
         'renderSelectAll'=> false,                                              // optional, default true
         'restockLabel'   => 'Add the sold stock back to inventory',             // optional, omit to hide the checkbox
     ])
--}}
@php
    $bd_scope = $scope;
    $bd_box = $checkboxClass ?? 'check_select';
    $bd_renderAll = $renderSelectAll ?? true;
    $bd_checkAll = $checkAllId ?? 'invCheckAll_' . $bd_scope;
    $bd_restock = $restockLabel ?? '';
    $bd_selLabel = $selectedLabel ?? 'Delete Selected';
    $bd_allLabel = $allLabel ?? 'Delete All';
    $bd_verb = $verb ?? 'delete';
@endphp

@if ($bd_renderAll)
    <div class="badge badge-soft-success align-items-center" style="height: 39px; display: flex;">
        <div class="form-check mr-1">
            <input type="checkbox" class="form-check-input" id="{{ $bd_checkAll }}">
            <label style="white-space: nowrap;" class="mt-1 form-check-label" id="{{ $bd_checkAll }}_label"
                for="{{ $bd_checkAll }}">Select All</label>
        </div>
    </div>
@endif

<div class="d-flex gap-1" id="invDel_{{ $bd_scope }}" data-url="{{ $deleteRoute }}"
    data-checkbox=".{{ $bd_box }}" data-checkall="{{ $bd_checkAll }}" data-own-checkall="{{ $bd_renderAll ? 1 : 0 }}"
    data-selected-text="{{ $selectedText }}" data-all-text="{{ $allText }}" data-restock="{{ $bd_restock }}"
    data-verb="{{ $bd_verb }}">
    <button type="button" style="white-space: nowrap; display:none;"
        class="btn btn-sm btn-outline-danger px-3 py-2 inv-report-delete" title="{{ $bd_selLabel }}">
        <i class="tio-delete"></i> {{ $bd_selLabel }}
    </button>
    <button type="button" style="white-space: nowrap;" class="btn btn-sm btn-danger px-3 py-2 inv-report-delete-all"
        title="{{ $bd_allLabel }}">
        <i class="tio-delete"></i> {{ $bd_allLabel }}
    </button>
</div>

<script>
    (function () {
        var wrap = document.getElementById('invDel_{{ $bd_scope }}');
        if (!wrap) return;

        var boxSel = wrap.getAttribute('data-checkbox');
        var checkAllId = wrap.getAttribute('data-checkall');
        var ownsCheckAll = wrap.getAttribute('data-own-checkall') === '1';
        var restockLabel = wrap.getAttribute('data-restock');
        var verb = wrap.getAttribute('data-verb') || 'delete';
        var selBtn = wrap.querySelector('.inv-report-delete');
        var allBtn = wrap.querySelector('.inv-report-delete-all');

        function checkedIds() {
            var out = [];
            var nodes = document.querySelectorAll(boxSel + ':checked');
            for (var i = 0; i < nodes.length; i++) out.push(nodes[i].value);
            return out;
        }

        function syncSelectedBtn() {
            selBtn.style.display = checkedIds().length ? '' : 'none';
        }

        // The page's own Select All flips the row boxes with jQuery .prop(), which fires no change
        // event, so re-read on the next tick rather than trusting the event target alone.
        document.addEventListener('change', function (e) {
            var t = e.target;
            if (!t) return;
            var isRow = t.className && (' ' + t.className + ' ').indexOf(' ' + boxSel.slice(1) + ' ') > -1;
            if (isRow || t.id === checkAllId) setTimeout(syncSelectedBtn, 0);
        });

        if (ownsCheckAll) {
            var master = document.getElementById(checkAllId);
            var masterLbl = document.getElementById(checkAllId + '_label');
            if (master) {
                master.addEventListener('change', function () {
                    var nodes = document.querySelectorAll(boxSel);
                    for (var i = 0; i < nodes.length; i++) nodes[i].checked = master.checked;
                    if (masterLbl) masterLbl.textContent = master.checked ? 'Deselect All' : 'Select All';
                    syncSelectedBtn();
                });
            }
        }

        function post(payload, url) {
            var $ = window.jQuery;
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            $.post({
                url: url,
                data: payload,
                success: function () { window.location.reload(); },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || ('Could not ' + verb + ' the selected records.');
                    if (window.Swal) {
                        Swal.fire({ title: 'Failed', text: msg, type: 'error' });
                    } else {
                        alert(msg);
                    }
                }
            });
        }

        // "Delete All" has to carry the page's filters, which live in the query string.
        function urlWithFilters() {
            var base = wrap.getAttribute('data-url');
            var search = window.location.search;
            if (!search) return base;
            return base + (base.indexOf('?') > -1 ? '&' + search.slice(1) : search);
        }

        function confirmThen(what, run) {
            if (!window.Swal) {
                if (window.confirm('Are you sure you want to ' + verb + ' ' + what + '? This cannot be undone.')) run(0);
                return;
            }
            var html = '<p class="mb-2">You want to ' + verb + ' ' + what + '. This action cannot be undone.</p>';
            if (restockLabel) {
                html += '<div class="form-check text-left d-inline-block">' +
                    '<input type="checkbox" class="form-check-input" id="invBulkRestock">' +
                    '<label class="form-check-label" for="invBulkRestock">' + restockLabel + '</label>' +
                    '</div>';
            }
            Swal.fire({
                title: '{{ translate('messages.Are you sure?') }}',
                html: html,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.Yes') }}',
                reverseButtons: true,
                preConfirm: function () {
                    var cb = document.getElementById('invBulkRestock');
                    return { restock: cb && cb.checked ? 1 : 0 };
                }
            }).then(function (result) {
                if (result.value) run(result.value.restock || 0);
            });
        }

        selBtn.addEventListener('click', function () {
            var ids = checkedIds();
            if (!ids.length) {
                alert('Please select at least one record.');
                return;
            }
            confirmThen(wrap.getAttribute('data-selected-text') + ' (' + ids.length + ')', function (restock) {
                post({ ids: ids, restock: restock }, wrap.getAttribute('data-url'));
            });
        });

        allBtn.addEventListener('click', function () {
            confirmThen(wrap.getAttribute('data-all-text'), function (restock) {
                post({ delete_all: 1, restock: restock }, urlWithFilters());
            });
        });

        syncSelectedBtn();
    })();
</script>
