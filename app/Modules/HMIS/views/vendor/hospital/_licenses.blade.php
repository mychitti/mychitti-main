@php
    $uid         = $uid ?? 'lic';
    $field       = $field ?? 'licenses';
    $rows        = collect($licenses ?? []);
    $note        = $note ?? 'Add every registration this department holds. Leave a row blank and it is ignored.';
    $suggestions = $suggestions ?? [];
@endphp

<div class="license-repeater" data-uid="{{ $uid }}" data-field="{{ $field }}">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <label class="input-label mb-0"><i class="tio-certificate mr-1"></i> Licences &amp; Registrations</label>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addLicenseRow('{{ $uid }}')">
            <i class="tio-add mr-1"></i> Add Licence
        </button>
    </div>
    <small class="text-muted d-block mb-2">{{ $note }}</small>

    @if (count($suggestions))
        <datalist id="licTypes-{{ $uid }}">
            @foreach ($suggestions as $suggestion)
                <option value="{{ $suggestion }}"></option>
            @endforeach
        </datalist>
    @endif

    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-1">
            <thead class="thead-light">
                <tr>
                    <th style="width:22%">Licence Type</th>
                    <th style="width:22%">Licence Number <span class="text-danger">*</span></th>
                    <th style="width:22%">Issuing Authority</th>
                    <th style="width:15%">Issued On</th>
                    <th style="width:15%">Valid Till</th>
                    <th style="width:4%"></th>
                </tr>
            </thead>
            <tbody id="licBody-{{ $uid }}">
                @foreach ($rows as $i => $license)
                    <tr>
                        <td>
                            <input type="text" class="form-control form-control-sm"
                                name="{{ $field }}[{{ $i }}][license_type]"
                                value="{{ $license->license_type }}"
                                list="{{ count($suggestions) ? 'licTypes-' . $uid : '' }}"
                                placeholder="e.g. NABL">
                            <input type="hidden" name="{{ $field }}[{{ $i }}][id]" value="{{ $license->id }}">
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm"
                                name="{{ $field }}[{{ $i }}][license_no]"
                                value="{{ $license->license_no }}" placeholder="Licence / Reg. no.">
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm"
                                name="{{ $field }}[{{ $i }}][issuing_authority]"
                                value="{{ $license->issuing_authority }}" placeholder="Issuing body">
                        </td>
                        <td>
                            <input type="date" class="form-control form-control-sm"
                                name="{{ $field }}[{{ $i }}][issued_on]"
                                value="{{ $license->issued_on?->format('Y-m-d') }}">
                        </td>
                        <td>
                            <input type="date" class="form-control form-control-sm"
                                name="{{ $field }}[{{ $i }}][valid_till]"
                                value="{{ $license->valid_till?->format('Y-m-d') }}">
                            @if ($license->isExpired())
                                <small class="text-danger">Expired</small>
                            @elseif ($license->expiresSoon())
                                <small class="text-warning">Expiring soon</small>
                            @endif
                        </td>
                        <td class="text-center align-middle">
                            <a href="javascript:void(0)" class="text-danger" title="Remove"
                                onclick="removeLicenseRow(this, '{{ $uid }}')"><i class="tio-delete"></i></a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <small class="text-muted lic-empty-{{ $uid }}" style="{{ $rows->count() ? 'display:none' : '' }}">
        No licence added yet.
    </small>
</div>

@once
    @push('script_2')
    <script>
        window.__licIndex = window.__licIndex || {};

        function addLicenseRow(uid) {
            const wrap  = document.querySelector('.license-repeater[data-uid="' + uid + '"]');
            const body  = document.getElementById('licBody-' + uid);
            if (!wrap || !body) return;

            const field = wrap.dataset.field;
            const list  = document.getElementById('licTypes-' + uid) ? 'licTypes-' + uid : '';
            const start = body.querySelectorAll('tr').length;
            window.__licIndex[uid] = Math.max(window.__licIndex[uid] || 0, start);
            const i = window.__licIndex[uid]++;
            const n = (key) => field + '[' + i + '][' + key + ']';

            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td><input type="text" class="form-control form-control-sm" name="' + n('license_type') + '" list="' + list + '" placeholder="e.g. NABL">' +
                '<input type="hidden" name="' + n('id') + '" value=""></td>' +
                '<td><input type="text" class="form-control form-control-sm" name="' + n('license_no') + '" placeholder="Licence / Reg. no."></td>' +
                '<td><input type="text" class="form-control form-control-sm" name="' + n('issuing_authority') + '" placeholder="Issuing body"></td>' +
                '<td><input type="date" class="form-control form-control-sm" name="' + n('issued_on') + '"></td>' +
                '<td><input type="date" class="form-control form-control-sm" name="' + n('valid_till') + '"></td>' +
                '<td class="text-center align-middle"><a href="javascript:void(0)" class="text-danger" title="Remove" onclick="removeLicenseRow(this, \'' + uid + '\')"><i class="tio-delete"></i></a></td>';
            body.appendChild(tr);
            toggleLicenseEmpty(uid);
        }

        function removeLicenseRow(el, uid) {
            el.closest('tr').remove();
            toggleLicenseEmpty(uid);
        }

        function toggleLicenseEmpty(uid) {
            const body  = document.getElementById('licBody-' + uid);
            const empty = document.querySelector('.lic-empty-' + uid);
            if (body && empty) empty.style.display = body.querySelectorAll('tr').length ? 'none' : '';
        }
    </script>
    @endpush
@endonce
