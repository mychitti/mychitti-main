{{-- Save / load reusable prescription templates.

     A doctor writes the same regimen for the same complaint all day. This keeps a filled
     prescription by name — diagnosis, advice, follow-up interval and every medicine line — and
     drops it back into the form to be edited per patient.

     Included by both Rx screens, which share #medTable and the _med_row partial but not their
     add-row function, so the caller passes its own:

       @include('hmis::vendor.prescription._rx_templates', [
           'formId'   => 'customRxForm',
           'addRowFn' => 'addCustomMedRow',
       ])
--}}
@php
    $formId   = $formId ?? 'rxForm';
    $addRowFn = $addRowFn ?? 'addMedRow';
@endphp

<div class="rx-tpl-bar">
    <button type="button" class="btn btn-sm btn-outline-primary" onclick="rxTemplateOpen()">
        <i class="tio-folder-opened mr-1"></i> Load Template
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="rxTemplateSaveOpen()">
        <i class="tio-bookmark-outlined mr-1"></i> Save as Template
    </button>
</div>

{{-- Load --}}
<div class="modal fade" id="rxTplLoadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0"><i class="tio-folder-opened mr-1"></i> Load Prescription Template</h6>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="text" id="rxTplSearch" class="form-control form-control-sm mb-2"
                       placeholder="Search templates..." oninput="rxTemplateFilter(this.value)">
                <div id="rxTplList" style="max-height:340px; overflow-y:auto;">
                    <div class="text-muted text-center py-4" style="font-size:13px;">Loading...</div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <small class="text-muted mr-auto">Loading a template replaces the diagnosis, advice, follow-up and medicine rows.</small>
                <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

{{-- Save --}}
<div class="modal fade" id="rxTplSaveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0"><i class="tio-bookmark-outlined mr-1"></i> Save as Template</h6>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="input-label">Template Name <span class="text-danger">*</span></label>
                    <input type="text" id="rxTplName" class="form-control form-control-sm"
                           placeholder="e.g. Back Pain — standard" maxlength="190">
                    <small class="text-muted">Saving under a name you already use overwrites that template.</small>
                </div>
                <label class="d-flex align-items-center mb-0" style="font-size:13px; cursor:pointer;">
                    <input type="checkbox" id="rxTplShared" class="mr-2">
                    Share with every doctor in this hospital
                </label>
                <div id="rxTplSaveMsg" class="mt-2" style="font-size:12px;"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn--primary" id="rxTplSaveBtn" onclick="rxTemplateSave()">Save Template</button>
            </div>
        </div>
    </div>
</div>

@push('css_or_js')
<style>
    .rx-tpl-bar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
    .rx-tpl-item {
        display:flex; align-items:center; justify-content:space-between; gap:10px;
        padding:9px 12px; border:1px solid #e9edf2; border-radius:8px; margin-bottom:6px;
        cursor:pointer; background:#fff;
    }
    .rx-tpl-item:hover { border-color:#93b4fd; background:#f8faff; }
    .rx-tpl-name { font-weight:600; font-size:13px; color:#0f172a; }
    .rx-tpl-sub { font-size:11px; color:#8d97a5; margin-top:2px; }
    .rx-tpl-del { color:#cfd6e0; font-size:15px; padding:2px 6px; }
    .rx-tpl-del:hover { color:#dc3545; }
</style>
@endpush

@push('script_2')
<script>
(function () {
    const formId   = @json($formId);
    const addRowFn = @json($addRowFn);
    const urls = {
        list:   "{{ route('vendor.prescription.templates') }}",
        show:   "{{ route('vendor.prescription.templates.show', '__ID__') }}",
        save:   "{{ route('vendor.prescription.templates.save') }}",
        remove: "{{ route('vendor.prescription.templates.delete', '__ID__') }}",
    };
    const csrf = "{{ csrf_token() }}";
    let loaded = [];

    const form = () => document.getElementById(formId);
    const field = (name) => form() ? form().querySelector('[name="' + name + '"]') : null;

    // The doctor is whoever this form is already writing for; the standalone form leaves it to
    // the server to resolve from the logged-in employee.
    function doctorId() {
        const el = field('doctor_profile_id');
        return el && el.value ? el.value : '';
    }

    function rxTemplateOpen() {
        $('#rxTplLoadModal').modal('show');
        const list = document.getElementById('rxTplList');
        list.innerHTML = '<div class="text-muted text-center py-4" style="font-size:13px;">Loading...</div>';

        fetch(urls.list + '?doctor_profile_id=' + encodeURIComponent(doctorId()), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                loaded = (data && data.templates) || [];
                rxTemplateFilter(document.getElementById('rxTplSearch').value || '');
            })
            .catch(() => {
                list.innerHTML = '<div class="text-danger text-center py-4" style="font-size:13px;">Could not load templates.</div>';
            });
    }

    function rxTemplateFilter(term) {
        const list = document.getElementById('rxTplList');
        const q = (term || '').trim().toLowerCase();
        const rows = loaded.filter(t =>
            !q || (t.name || '').toLowerCase().includes(q) || (t.diagnosis || '').toLowerCase().includes(q));

        if (!rows.length) {
            list.innerHTML = '<div class="text-muted text-center py-4" style="font-size:13px;">'
                + (loaded.length ? 'No template matches that.' : 'No templates saved yet. Fill a prescription and use "Save as Template".')
                + '</div>';
            return;
        }

        list.innerHTML = rows.map(t => {
            const bits = [t.item_count + ' medicine' + (t.item_count === 1 ? '' : 's')];
            if (t.diagnosis) bits.push(esc(t.diagnosis));
            if (t.is_shared) bits.push('Shared' + (t.owner && !t.is_mine ? ' by ' + esc(t.owner) : ''));
            return '<div class="rx-tpl-item" onclick="rxTemplateApply(' + t.id + ')">'
                + '<div style="flex:1">'
                + '<div class="rx-tpl-name">' + esc(t.name) + '</div>'
                + '<div class="rx-tpl-sub">' + bits.join(' &middot; ') + '</div>'
                + '</div>'
                + (t.can_delete
                    ? '<a class="rx-tpl-del" title="Delete" onclick="event.stopPropagation(); rxTemplateDelete(' + t.id + ')"><i class="tio-delete-outlined"></i></a>'
                    : '')
                + '</div>';
        }).join('');
    }

    function esc(v) {
        const d = document.createElement('div');
        d.textContent = v == null ? '' : v;
        return d.innerHTML;
    }

    function rxTemplateApply(id) {
        fetch(urls.show.replace('__ID__', id) + '?doctor_profile_id=' + encodeURIComponent(doctorId()), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data || !data.status) { alert((data && data.message) || 'Could not load that template.'); return; }
                const t = data.template;

                setValue('diagnosis', t.diagnosis);
                setValue('notes', t.notes);
                setValue('follow_up_date', t.follow_up_date);

                // Clear the table outright rather than filling the blank first row: a half-typed
                // prescription merged into a template is worse than an explicit replacement.
                document.getElementById('medTable').innerHTML = '';
                (t.items || []).forEach(item => window[addRowFn]());
                applyItems(t.items || []);

                if (!(t.items || []).length) window[addRowFn]();

                $('#rxTplLoadModal').modal('hide');
            })
            .catch(() => alert('Could not load that template.'));
    }

    function setValue(name, value) {
        const el = field(name);
        if (el) el.value = value == null ? '' : value;
    }

    // Rows are created by the page's own add-row function so the markup always matches the
    // partial; this only fills the freshly created lines in order.
    function applyItems(items) {
        const rows = document.querySelectorAll('#medTable .med-row');
        items.forEach((item, i) => {
            const row = rows[i];
            if (!row) return;
            Object.keys(item).forEach(key => {
                const el = row.querySelector('[name$="[' + key + ']"]');
                if (!el) return;
                const value = item[key] == null ? '' : item[key];
                if (el.tagName === 'SELECT' && value && !Array.from(el.options).some(o => o.value === value)) {
                    el.add(new Option(value, value));
                }
                el.value = value;
                // select2 paints its own box and does not watch the underlying select, so a
                // value set from script has to be handed to it explicitly.
                if (el.classList.contains('rx-med-select') && window.jQuery && jQuery.fn.select2) {
                    jQuery(el).trigger('change.select2');
                }
            });
            // Let the page's own banned-medicine watcher see the filled name — it listens for
            // input on the table and would otherwise never fire on a value set from script.
            const nameEl = row.querySelector('[name$="[medicine_name]"]');
            if (nameEl) nameEl.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }

    function rxTemplateSaveOpen() {
        document.getElementById('rxTplSaveMsg').innerHTML = '';
        document.getElementById('rxTplName').value = (field('diagnosis') && field('diagnosis').value.trim()) || '';
        $('#rxTplSaveModal').modal('show');
    }

    function rxTemplateSave() {
        const name = document.getElementById('rxTplName').value.trim();
        const msg  = document.getElementById('rxTplSaveMsg');
        if (!name) { msg.innerHTML = '<span class="text-danger">Give the template a name.</span>'; return; }

        const body = new FormData();
        body.append('_token', csrf);
        body.append('name', name);
        body.append('is_shared', document.getElementById('rxTplShared').checked ? 1 : 0);
        body.append('doctor_profile_id', doctorId());
        ['diagnosis', 'notes', 'follow_up_date'].forEach(n => {
            const el = field(n);
            if (el) body.append(n, el.value || '');
        });

        // Re-indexed from scratch: deleted rows leave gaps in the on-screen names, and a gappy
        // medicines[] array arrives at PHP with holes the validator will not walk.
        let i = 0;
        document.querySelectorAll('#medTable .med-row').forEach(row => {
            const nameEl = row.querySelector('[name$="[medicine_name]"]');
            if (!nameEl || !nameEl.value.trim()) return;
            row.querySelectorAll('[name*="medicines["]').forEach(el => {
                const key = (el.name.match(/\[([a-z_]+)\]$/) || [])[1];
                if (key) body.append('medicines[' + i + '][' + key + ']', el.value || '');
            });
            i++;
        });

        const btn = document.getElementById('rxTplSaveBtn');
        btn.disabled = true;
        msg.innerHTML = '<span class="text-muted">Saving...</span>';

        fetch(urls.save, { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json().then(d => ({ ok: r.ok, d: d })))
            .then(res => {
                btn.disabled = false;
                if (!res.ok || !res.d.status) {
                    msg.innerHTML = '<span class="text-danger">' + esc(res.d.message || 'Could not save the template.') + '</span>';
                    return;
                }
                msg.innerHTML = '<span class="text-success">' + esc(res.d.message) + '</span>';
                setTimeout(() => $('#rxTplSaveModal').modal('hide'), 900);
            })
            .catch(() => {
                btn.disabled = false;
                msg.innerHTML = '<span class="text-danger">Could not save the template.</span>';
            });
    }

    function rxTemplateDelete(id) {
        if (!confirm('Delete this template? Prescriptions already written from it are not affected.')) return;

        const body = new FormData();
        body.append('_token', csrf);
        body.append('doctor_profile_id', doctorId());

        fetch(urls.remove.replace('__ID__', id), { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json().then(d => ({ ok: r.ok, d: d })))
            .then(res => {
                if (!res.ok || !res.d.status) { alert((res.d && res.d.message) || 'Could not delete that template.'); return; }
                loaded = loaded.filter(t => t.id !== id);
                rxTemplateFilter(document.getElementById('rxTplSearch').value || '');
            })
            .catch(() => alert('Could not delete that template.'));
    }

    window.rxTemplateOpen     = rxTemplateOpen;
    window.rxTemplateFilter   = rxTemplateFilter;
    window.rxTemplateApply    = rxTemplateApply;
    window.rxTemplateSaveOpen = rxTemplateSaveOpen;
    window.rxTemplateSave     = rxTemplateSave;
    window.rxTemplateDelete   = rxTemplateDelete;
})();
</script>
@endpush
