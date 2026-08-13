{{-- Documents this patient has been sent, and the way to send another.
     Expects: $patient, $sentDocs (rows from wa_patient_shares, newest first) --}}
@php
    $sentKindMeta = [
        'treatment'        => ['Consultation summary', '#dbeafe'],
        'prescription'     => ['Prescription', '#d1fae5'],
        'prescription_pdf' => ['Prescription (PDF)', '#d1fae5'],
        'medicines'        => ['Medicine instructions', '#fef3c7'],
        'lab'              => ['Lab report', '#e0e7ff'],
        'radiology'        => ['Radiology report', '#cffafe'],
        'document'         => ['Document', '#ede9fe'],
    ];

    // Shared with the Sent Documents log, so the two screens cannot point at different records.
    $sentRecordUrl = fn($share) => \App\Services\HmisWhatsAppShare::panelUrl(
        $share->kind,
        $share->record_id ? (int) $share->record_id : null
    );
    $sentMask = fn($phone) => \App\Services\HmisWhatsAppShare::maskedPhone($phone);

    // A manually sent file points at a patient document, so the row can offer the file itself.
    $sentFiles = $patient->documents->keyBy('id');
@endphp

@if(hasPermission('patient_documents', 'list'))
<div class="card mt-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
        <div>
            <h6 class="mb-0"><i class="tio-send mr-1"></i> Documents Sent to Patient</h6>
            <span class="text-muted" style="font-size:12px;">
                Records shared on WhatsApp, and any file sent by hand.
            </span>
        </div>
        @if(hasPermission('patient_documents', 'add'))
            <button type="button" class="btn btn-sm btn--primary" onclick="openSendDocModal()">
                <i class="tio-attachment mr-1"></i> Send a document
            </button>
        @endif
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Document</th>
                    <th>Sent</th>
                    <th>To</th>
                    <th>Delivery</th>
                    <th>Opened</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sentDocs as $doc)
                    @php
                        $label   = $sentKindMeta[$doc->kind][0] ?? ucfirst(str_replace('_', ' ', $doc->kind));
                        $colour  = $sentKindMeta[$doc->kind][1] ?? '#f3f4f6';
                        $title   = $doc->title ?? null;
                        $asPdf   = ($doc->sent_as ?? 'link') === 'pdf';
                        $expired = !$asPdf && $doc->expires_at && \Carbon\Carbon::parse($doc->expires_at)->isPast();
                        $revoked = !$asPdf && $doc->revoked_at;
                        $live    = !$asPdf && !$expired && !$revoked;
                        $views   = (int) ($doc->views ?? 0);
                        $url     = $sentRecordUrl($doc);
                        // A hand-sent file has no record page — offer the file on the patient's
                        // own record instead, as long as it hasn't since been deleted.
                        if (!$url && $doc->kind === 'document' && isset($sentFiles[$doc->record_id])) {
                            $url = asset('storage/' . $sentFiles[$doc->record_id]->file_path);
                        }
                    @endphp
                    <tr>
                        <td>
                            <span class="badge" style="font-size:11px; background:{{ $colour }}; color:#374151; font-weight:600;">
                                {{ $label }}
                            </span>
                            @if($title)
                                <div class="text-muted" style="font-size:11.5px;">{{ $title }}</div>
                            @endif
                        </td>
                        <td style="font-size:12px;">
                            {{ \Carbon\Carbon::parse($doc->created_at)->format('d M Y') }}
                            <div class="text-muted" style="font-size:11px;">{{ \Carbon\Carbon::parse($doc->created_at)->format('h:i A') }}</div>
                        </td>
                        <td style="font-size:12px;">{{ $doc->sent_to ? $sentMask($doc->sent_to) : '—' }}</td>
                        <td style="font-size:12px;">
                            @if($asPdf)
                                <span class="text-muted"><i class="tio-attachment mr-1"></i>File</span>
                            @elseif($revoked)
                                <span class="text-danger"><i class="tio-blocked mr-1"></i>Revoked</span>
                            @elseif($expired)
                                <span class="text-muted"><i class="tio-time mr-1"></i>Expired</span>
                            @else
                                <span class="text-primary"><i class="tio-link mr-1"></i>Link</span>
                            @endif
                        </td>
                        <td style="font-size:12px;">
                            @if($asPdf)
                                <span class="text-muted">—</span>
                            @elseif($views > 0)
                                <span class="text-success font-weight-bold">{{ $views }}×</span>
                                <div class="text-muted" style="font-size:11px;">
                                    {{ \Carbon\Carbon::parse($doc->last_viewed_at ?: $doc->created_at)->diffForHumans() }}
                                </div>
                            @else
                                <span class="text-warning">Not yet</span>
                            @endif
                        </td>
                        <td class="text-right" style="white-space:nowrap;">
                            @if($live)
                                <button type="button" class="btn btn-xs btn-soft-secondary"
                                        onclick="copySentLink(this, '{{ route('patient-record', ['token' => $doc->token]) }}')"
                                        title="Copy the link that was sent">
                                    <i class="tio-link"></i>
                                </button>
                            @endif
                            @if($url)
                                <a href="{{ $url }}" target="_blank" class="btn btn-xs btn-soft-primary" title="Open what was sent">
                                    <i class="tio-visible"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="tio-send" style="font-size:26px;opacity:.3;display:block;margin-bottom:6px;"></i>
                            Nothing has been sent to this patient yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

@if(hasPermission('patient_documents', 'add'))
<div class="modal fade" id="sendDocModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="tio-attachment mr-1"></i> Send a document to {{ $patient->name }}</h6>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label style="font-size:12px;">From this patient's documents</label>
                    <select id="sendDocExisting" class="form-control form-control-sm">
                        <option value="">— Choose a document on file —</option>
                        @foreach($patient->documents as $pd)
                            <option value="{{ $pd->id }}" data-name="{{ $pd->document_name }}">
                                {{ $pd->document_name ?: ucfirst(str_replace('_', ' ', $pd->document_type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="text-center text-muted my-1" style="font-size:11px;">— or —</div>

                <div class="form-group">
                    <label style="font-size:12px;">Upload a new file</label>
                    <input type="file" id="sendDocFile" class="form-control form-control-sm"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp">
                    <small class="text-muted" style="font-size:11px;">
                        Saved to this patient's documents as well as sent. Photos are converted to PDF — WhatsApp will not carry an image as a document.
                    </small>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-7">
                        <label style="font-size:12px;">What is it?</label>
                        <input type="text" id="sendDocTitle" class="form-control form-control-sm"
                               placeholder="e.g. Discharge summary" maxlength="100">
                    </div>
                    <div class="form-group col-md-5">
                        <label style="font-size:12px;">Send to</label>
                        <input type="text" id="sendDocPhone" class="form-control form-control-sm"
                               value="{{ $patient->phone }}" placeholder="Patient's number">
                    </div>
                </div>

                <div id="sendDocErr" class="text-danger small" style="display:none;"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn--primary" id="sendDocBtn" onclick="submitSendDoc()">
                    <i class="tio-send mr-1"></i> Send on WhatsApp
                </button>
            </div>
        </div>
    </div>
</div>

@push('script_2')
<script>
const sendDocUrl  = "{{ route('vendor.patient.send-document', $patient->id) }}";
const sendDocCsrf = "{{ csrf_token() }}";

function openSendDocModal() {
    document.getElementById('sendDocErr').style.display = 'none';
    $('#sendDocModal').modal('show');
}

// Picking one names it; the title is what the patient reads in the message, so it should not
// arrive blank just because nobody retyped the filename.
document.getElementById('sendDocExisting')?.addEventListener('change', function () {
    const title = document.getElementById('sendDocTitle');
    const picked = this.options[this.selectedIndex];
    if (this.value && !title.value) title.value = picked.getAttribute('data-name') || '';
});

function submitSendDoc() {
    const err   = document.getElementById('sendDocErr');
    const btn   = document.getElementById('sendDocBtn');
    const file  = document.getElementById('sendDocFile').files[0];
    const docId = document.getElementById('sendDocExisting').value;

    err.style.display = 'none';
    if (!file && !docId) {
        err.textContent = 'Choose a document on file, or upload one.';
        err.style.display = 'block';
        return;
    }

    const data = new FormData();
    data.append('_token', sendDocCsrf);
    if (file) data.append('file', file);
    if (docId) data.append('document_id', docId);
    data.append('title', document.getElementById('sendDocTitle').value);
    data.append('phone', document.getElementById('sendDocPhone').value);

    btn.disabled = true;
    btn.innerHTML = 'Sending…';

    fetch(sendDocUrl, { method: 'POST', body: data, headers: { 'X-CSRF-TOKEN': sendDocCsrf } })
        .then(r => r.json().then(d => ({ ok: r.ok, d })))
        .then(({ ok, d }) => {
            if (!ok || !d.ok) {
                err.textContent = d.message || 'Could not send that document.';
                err.style.display = 'block';
                return;
            }
            // Reload so the sent list, and the patient's documents if this was an upload, both
            // show what just happened rather than a stale copy of the page.
            if (window.toastr) toastr.success(d.message);
            location.reload();
        })
        .catch(() => {
            err.textContent = 'Could not send that document. Please try again.';
            err.style.display = 'block';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="tio-send mr-1"></i> Send on WhatsApp';
        });
}
</script>
@endpush
@endif
