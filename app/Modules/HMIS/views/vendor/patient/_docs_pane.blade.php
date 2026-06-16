{{-- Reusable documents pane (upload form + list) for one category.
     Expects: $cat (med|govt), $list_id, $docs (collection), $types (array of keys), $placeholder, $meta --}}
<div style="padding:6px 16px; border-bottom:1px solid #f3f4f6;">
    @if(hasPermission('patient_documents', 'add'))
    <button onclick="toggleUploadForm('{{ $cat }}')" id="{{ $cat }}ToggleBtn"
            style="background:none; border:none; padding:0; font-size:12px; color:red; cursor:pointer;">
        <i class="tio-add-circle mr-1"></i> Upload documents
    </button>
    @endif
</div>

@if(hasPermission('patient_documents', 'add'))
<div id="{{ $cat }}UploadForm" style="display:none; padding:10px 16px; border-bottom:1px solid #f0f0f0; background:#fafafa;">
    <div class="d-flex gap-2 align-items-end flex-wrap">
        <div style="flex:1; min-width:110px;">
            <label style="font-size:11px; color:#6b7280; margin-bottom:3px; display:block;">Type</label>
            <select id="{{ $cat }}TypeSelect" class="form-control form-control-sm">
                @foreach($types as $t)
                    <option value="{{ $t }}">{{ $meta[$t][0] ?? ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1; min-width:110px;">
            <label style="font-size:11px; color:#6b7280; margin-bottom:3px; display:block;">Name <span style="opacity:.5;">({{ $placeholder }})</span></label>
            <input type="text" id="{{ $cat }}NameInput" class="form-control form-control-sm" placeholder="Optional">
        </div>
        <div style="flex:2; min-width:150px;">
            <label style="font-size:11px; color:#6b7280; margin-bottom:3px; display:block;">Files</label>
            <input type="file" id="{{ $cat }}FileInput" class="form-control form-control-sm" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
        </div>
        <button class="btn btn-sm btn--primary" onclick="uploadDocs('{{ $cat }}')" id="{{ $cat }}UploadBtn" style="white-space:nowrap;">
            <i class="tio-upload mr-1"></i> Upload
        </button>
    </div>
    <div id="{{ $cat }}UploadErr" class="text-danger small mt-1" style="display:none;"></div>
    <div id="{{ $cat }}UploadProgress" style="display:none; margin-top:6px;">
        <div class="progress" style="height:4px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
        </div>
    </div>
</div>
@endif

@if(hasPermission('patient_documents', 'list'))
<div style="padding:12px 16px; max-height:50vh; overflow-y:auto;">
    <ul class="list-group list-group-flush mb-0" id="{{ $list_id }}">
        @forelse($docs as $doc)
        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 doc-item" data-id="{{ $doc->id }}">
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
@endif
