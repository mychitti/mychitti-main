{{-- Complaint picker — select from the hospital's list or type a new one (Select2 tags), with the
     store's saved groups one click away.

     Shared by the OPD register/edit forms, the consultation screen and the dental intake so all of
     them write complaints the same way: posted as an array, stored comma-separated in
     chief_complaint, split back by OpdVisit::splitTerms(), and anything typed by hand joins the
     store's list via OpdClinicalTerm::absorb().

     Groups live in a dialog rather than on the page: a busy OPD builds a dozen of them, and a row
     of badges that grows without limit pushes the field the doctor actually types in off-screen.

     $field       input name (chief_complaint / problem). Ignored when $id is given.
     $id          override the select's id, for a screen that saves it with its own JS
     $selected    terms already on the record
     $options     the store's complaint list
     $groups      OpdComplaintGroup rows
     $required    whether the picker must not be left empty
     $autoInit    false where the field starts hidden — Select2 mis-sizes against a hidden container --}}
@php
    $field    = $field ?? 'chief_complaint';
    $pickerId = $id ?? ('complaintPicker_' . $field);
    $selected = collect($selected ?? [])->filter()->values();
    $choices  = collect($options ?? [])->merge($selected)->unique();
@endphp

<select @if(!isset($id)) name="{{ $field }}[]" @endif id="{{ $pickerId }}" multiple
    class="form-control @error($field) is-invalid @enderror" @if($required ?? false) required @endif>
    @foreach ($choices as $term)
        <option value="{{ $term }}" @if($selected->contains($term)) selected @endif>{{ $term }}</option>
    @endforeach
</select>
@error($field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
@error($field . '.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

<div class="cc-tools">
    <button type="button" class="cc-tool" onclick="openComplaintGroups('{{ $pickerId }}')">
        <i class="tio-folder-outlined"></i> Load groups<span class="cc-tool-count" data-cc-count>0</span>
    </button>
    <button type="button" class="cc-tool" onclick="saveComplaintGroup('{{ $pickerId }}')">
        <i class="tio-bookmark-outlined"></i> Save as group
    </button>
    <span class="cc-hint">Pick from the list, or type a new one and press Enter.</span>
</div>

@if ($autoInit ?? true)
@push('script_2')
<script>
    (function () {
        // Select2 is loaded by the layout; if it isn't on this page the plain multi-select still
        // works, so the field is never unusable.
        if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
        jQuery('#{{ $pickerId }}').select2({
            tags: true,
            width: '100%',
            tokenSeparators: [','],
            placeholder: '{{ $placeholder ?? 'Select or type a complaint…' }}',
            containerCssClass: 'cc-select2'
        });
    })();
</script>
@endpush
@endif

{{-- One dialog, one copy of the behaviour, however many pickers a page renders. --}}
@once
@push('css_or_js')
<style>
    .cc-tools { display:flex; align-items:center; flex-wrap:wrap; gap:8px; margin-top:7px; }
    .cc-tool {
        display:inline-flex; align-items:center; gap:5px;
        border:1px solid #e3e7ef; background:#fff; color:#5b6b7f;
        border-radius:7px; padding:3px 9px; font-size:11.5px; font-weight:600;
        cursor:pointer; line-height:1.6;
    }
    .cc-tool:hover { border-color:#bfdbfe; color:#1d4ed8; background:#f8fbff; }
    .cc-tool i { font-size:13px; }
    .cc-tool-count {
        background:#eef2f7; color:#64748b; border-radius:20px;
        font-size:10px; font-weight:700; padding:0 6px; margin-left:2px;
    }
    .cc-hint { font-size:11px; color:#94a3b8; margin-left:auto; }

    .cc-grp-row {
        display:flex; align-items:center; gap:10px;
        border:1px solid #eef0f5; border-radius:10px; padding:9px 12px; margin-bottom:7px;
    }
    .cc-grp-row:hover { border-color:#bfdbfe; background:#f8fbff; }
    .cc-grp-main { min-width:0; flex:1; }
    .cc-grp-name { font-size:13px; font-weight:700; color:#1f2937; }
    .cc-grp-terms {
        font-size:11.5px; color:#7b8794; margin-top:2px;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
    }
    .cc-grp-del { color:#c3ccd8; font-size:15px; cursor:pointer; padding:0 2px; }
    .cc-grp-del:hover { color:#dc3545; }
    .cc-grp-empty { text-align:center; color:#94a3b8; font-size:12.5px; padding:26px 10px; }
</style>
@endpush

@push('script_2')
<div class="modal fade" id="ccGroupModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" style="font-size:14px;font-weight:700;">Complaint groups</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body py-3" id="ccGroupModalList"></div>
        </div>
    </div>
</div>

<script>
    const ccGroupStoreUrl  = "{{ route('vendor.opd.complaint-groups.store') }}";
    const ccGroupDelUrlTpl = "{{ route('vendor.opd.complaint-groups.destroy', '__ID__') }}";
    const ccCsrf           = "{{ csrf_token() }}";

    // Held in JS rather than in the DOM so every picker on the page reads one list and they can
    // never disagree after a save or a delete.
    let ccGroups = @json(collect($groups ?? [])->map(fn($g) => ['id' => $g->id, 'name' => $g->name, 'terms' => $g->term_list])->values());
    let ccActivePicker = null;

    function ccRefreshCounts() {
        document.querySelectorAll('[data-cc-count]').forEach(el => {
            el.textContent = ccGroups.length;
            el.style.display = ccGroups.length ? '' : 'none';
        });
    }

    /** Select a term on a picker, creating the option when the list has never seen it. */
    function ccAddTerm(selectId, term) {
        const el = document.getElementById(selectId);
        if (!el) return;

        let option = Array.from(el.options).find(o => o.value.toLowerCase() === term.toLowerCase());
        if (!option) {
            option = new Option(term, term, true, true);
            el.appendChild(option);
        }
        option.selected = true;

        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) jQuery(el).trigger('change');
    }

    function ccSelectedTerms(selectId) {
        const el = document.getElementById(selectId);
        if (!el) return [];
        return Array.from(el.selectedOptions).map(o => o.value.trim()).filter(v => v.length);
    }

    function ccEscape(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
    }

    function openComplaintGroups(selectId) {
        ccActivePicker = selectId;
        ccRenderGroupModal();
        if (typeof jQuery !== 'undefined') jQuery('#ccGroupModal').modal('show');
    }

    function ccRenderGroupModal() {
        const box = document.getElementById('ccGroupModalList');

        if (!ccGroups.length) {
            box.innerHTML = '<div class="cc-grp-empty">No groups yet.<br>'
                + 'Pick the complaints you keep recording together, then <b>Save as group</b>.</div>';
            return;
        }

        box.innerHTML = ccGroups.map(g => `
            <div class="cc-grp-row">
                <div class="cc-grp-main" onclick="applyComplaintGroup(${g.id})" style="cursor:pointer;"
                    title="${ccEscape(g.terms.join(', '))}">
                    <div class="cc-grp-name">${ccEscape(g.name)}</div>
                    <div class="cc-grp-terms">${ccEscape(g.terms.join(' · '))}</div>
                </div>
                <button type="button" class="btn btn-xs btn-soft-primary" onclick="applyComplaintGroup(${g.id})">Add</button>
                <a class="cc-grp-del" title="Delete this group" onclick="deleteComplaintGroup(${g.id})">&times;</a>
            </div>`).join('');
    }

    // A group ADDS to the current selection rather than replacing it: two presentations often turn
    // up in the same patient, and clearing what the doctor already picked would be rude.
    function applyComplaintGroup(id) {
        const group = ccGroups.find(g => g.id === id);
        if (!group || !ccActivePicker) return;

        group.terms.forEach(term => ccAddTerm(ccActivePicker, term));
        if (typeof jQuery !== 'undefined') jQuery('#ccGroupModal').modal('hide');
    }

    function saveComplaintGroup(selectId) {
        const terms = ccSelectedTerms(selectId);
        if (!terms.length) {
            alert('Pick the complaints first, then save them as a group.');
            return;
        }

        const name = (prompt('Name this group (e.g. Diabetes screen):') || '').trim();
        if (!name) return;

        fetch(ccGroupStoreUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': ccCsrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ name: name, terms: terms })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) { alert(data.msg || 'Could not save the group.'); return; }

            // Saving over an existing name refreshes it in place instead of listing it twice.
            ccGroups = ccGroups.filter(g => g.id !== data.group.id).concat([data.group]);
            ccGroups.sort((a, b) => a.name.localeCompare(b.name));
            ccRefreshCounts();
            ccRenderGroupModal();
        })
        .catch(() => alert('Could not save the group.'));
    }

    function deleteComplaintGroup(id) {
        if (!confirm('Delete this group? The complaints themselves are not affected.')) return;

        fetch(ccGroupDelUrlTpl.replace('__ID__', id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': ccCsrf, 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) return;
            ccGroups = ccGroups.filter(g => g.id !== id);
            ccRefreshCounts();
            ccRenderGroupModal();
        })
        .catch(() => alert('Could not delete the group.'));
    }

    ccRefreshCounts();
</script>
@endpush
@endonce
