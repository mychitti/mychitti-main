@extends('layouts.admin.app')

@section('title', 'Knowledge Base')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <i class="tio-book-outlined text-primary" style="font-size:24px"></i>
            </span>
            <span>Knowledge Base</span>
        </h1>
        <p class="text-muted mt-1 mb-0">Documents ingested here are used to answer vendor AI chat questions.</p>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    <div class="row g-3">
        {{-- Add Document --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Add Document</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.knowledge-base.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                placeholder="e.g. Delivery Policy" value="{{ old('title') }}" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="input-label">Category</label>
                            <input type="text" name="category" class="form-control"
                                placeholder="e.g. policy, faq, product" value="{{ old('category') }}">
                        </div>
                        <div class="form-group">
                            <label class="input-label">Language</label>
                            <select name="lang" class="form-control">
                                <option value="en" {{ old('lang') == 'en' ? 'selected' : '' }}>English</option>
                                <option value="indic" {{ old('lang') == 'indic' ? 'selected' : '' }}>Indic (Hindi/Tamil/etc)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="input-label">Content <span class="text-danger">*</span></label>
                            <textarea name="content" rows="8" class="form-control @error('content') is-invalid @enderror"
                                placeholder="Paste the full text of the document here..." required>{{ old('content') }}</textarea>
                            @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="tio-add mr-1"></i> Add to Knowledge Base
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Document List --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        Documents
                        <span class="badge badge-soft-dark ml-2">{{ count($documents) }}</span>
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-borderless table-align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Lang</th>
                                <th>Added</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($documents as $i => $doc)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $doc['title'] }}</td>
                                <td>
                                    @if($doc['category'])
                                        <span class="badge badge-soft-info">{{ $doc['category'] }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-soft-secondary">{{ $doc['lang'] ?? 'en' }}</span>
                                </td>
                                <td>
                                    @if($doc['created_at'])
                                        {{ \Carbon\Carbon::parse($doc['created_at'])->format('d M Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn--container justify-content-center">
                                        <button type="button" class="btn action-btn btn--primary btn-sm"
                                            onclick="openEdit({{ $doc['id'] }}, {{ json_encode($doc['title']) }}, {{ json_encode($doc['content'] ?? '') }}, {{ json_encode($doc['category'] ?? '') }}, {{ json_encode($doc['lang'] ?? 'en') }})">
                                            <i class="tio-edit"></i>
                                        </button>
                                        <form action="{{ route('admin.knowledge-base.destroy', $doc['id']) }}"
                                              method="POST" id="del-doc-{{ $doc['id'] }}"
                                              onsubmit="return confirm('Delete this document from the knowledge base?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn action-btn btn--danger btn-sm">
                                                <i class="tio-delete-outlined"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <img src="{{ asset('public/assets/admin/img/empty.png') }}" alt="" width="60" class="mb-3 d-block mx-auto">
                                    <p class="text-muted mb-0">No documents yet. Add your first document on the left.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Document</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="input-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="input-label">Category</label>
                                <input type="text" name="category" id="edit_category" class="form-control"
                                    placeholder="e.g. policy, faq, product">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="input-label">Language</label>
                                <select name="lang" id="edit_lang" class="form-control">
                                    <option value="en">English</option>
                                    <option value="indic">Indic (Hindi/Tamil/etc)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="input-label">Content <span class="text-danger">*</span></label>
                        <textarea name="content" id="edit_content" rows="10" class="form-control" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="tio-save mr-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    function openEdit(id, title, content, category, lang) {
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_content').value = content;
        document.getElementById('edit_category').value = category || '';
        document.getElementById('edit_lang').value = lang || 'en';
        document.getElementById('editForm').action = '/admin/knowledge-base/' + id;
        $('#editModal').modal('show');
    }
</script>
@endpush
