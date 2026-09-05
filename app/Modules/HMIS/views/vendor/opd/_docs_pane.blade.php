{{-- Reusable documents pane (uploaded list + upload card) for one category on the OPD Reports tab.
     Expects: $cat (med|govt), $list_id, $docs (collection), $types (array of keys), $placeholder, $meta --}}
{{-- overflow:hidden so the header band and the flush list rows are clipped to the card's
     rounded corners. Without it their square edges overrun the radius and the corners read as
     cut off. Safe on these two: neither holds a dropdown for it to clip. --}}
<div class="card shadow-none border mb-4" style="overflow:hidden">
    <div class="card-header py-2 bg-light"><h6 class="mb-0 font-weight-bold" style="font-size:12px">Uploaded Files</h6></div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush mb-0" id="{{ $list_id }}">
            @forelse($docs as $doc)
            <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2 doc-item" data-id="{{ $doc->id }}">
                <span style="font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:300px;">
                    <i class="tio-file mr-1 text-muted"></i>
                    <span class="badge" style="font-size:11px; background:{{ $meta[$doc->document_type][1] ?? '#f3f4f6' }}; color:#374151; font-weight:600;">
                        {{ $meta[$doc->document_type][0] ?? $doc->document_type }}
                    </span>
                    @if($doc->document_name)
                        <span class="text-muted ml-1" style="font-size:12px;">({{ $doc->document_name }})</span>
                    @endif
                </span>
                <div class="d-flex gap-1" style="flex-shrink:0;">
                    @if(hasPermission('patient_documents', 'view'))
                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-xs btn-soft-primary">
                        <i class="tio-visible"></i>
                    </a>
                    @endif
                    @if(hasPermission('patient_documents', 'delete'))
                    <button class="btn btn-xs btn-soft-danger" onclick="deleteDoc({{ $doc->id }}, this)" title="Delete">
                        <i class="tio-delete"></i>
                    </button>
                    @endif
                </div>
            </li>
            @empty
            <li class="list-group-item text-center text-muted py-4 px-0 doc-empty-state">
                <i class="tio-file" style="font-size:28px;opacity:.35;display:block;margin-bottom:6px;"></i>
                No documents yet.
            </li>
            @endforelse
        </ul>
    </div>
</div>

@if(hasPermission('patient_documents', 'add'))
<div class="card shadow-none border bg-light" style="overflow:hidden">
    <div class="card-header py-2 bg-white"><h6 class="mb-0 font-weight-bold" style="font-size:12px">Upload New Document</h6></div>
    <div class="card-body">
        <div class="row align-items-end gap-2">
            <div class="col-md-3">
                <label class="small text-muted mb-1 d-block">Type</label>
                <select id="{{ $cat }}TypeSelect" class="form-control form-control-sm">
                    @foreach($types as $t)
                        <option value="{{ $t }}">{{ $meta[$t][0] ?? ucfirst($t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="small text-muted mb-1 d-block">Custom Label Name</label>
                <input type="text" id="{{ $cat }}NameInput" class="form-control form-control-sm" placeholder="{{ $placeholder }}">
            </div>
            <div class="col-md-4">
                <label class="small text-muted mb-1 d-block">Files</label>
                <input type="file" id="{{ $cat }}FileInput" class="form-control form-control-sm" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary" onclick="uploadDocs('{{ $cat }}')" id="{{ $cat }}UploadBtn" style="white-space:nowrap;">
                    <i class="tio-upload mr-1"></i> Upload
                </button>
            </div>
        </div>
        <div id="{{ $cat }}UploadErr" class="text-danger small mt-1" style="display:none;"></div>
        <div id="{{ $cat }}UploadProgress" style="display:none; margin-top:6px;">
            <div class="progress" style="height:4px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
            </div>
        </div>
    </div>
</div>
@endif
