@extends('layouts.vendor.app')

@section('title', 'Auto-Reply Knowledge')

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title"><i class="tio-book-opened"></i> Auto-Reply Knowledge</h1>
            <form method="get" class="d-flex align-items-center" style="gap:6px;">
                <select name="type" class="form-control form-control-sm" style="width:auto;" onchange="this.form.submit()">
                    <option value="">All types</option>
                    @foreach ($docTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <p class="text-muted" style="font-size:13px;">
            Teach the auto-reply what your business knows. When a customer messages you on WhatsApp,
            answers come from your <b>active</b> documents — the more complete they are, the better the replies.
        </p>

        <div class="row">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Your Knowledge ({{ $docs->count() }})</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-align-middle mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Document</th>
                                        <th>Type</th>
                                        <th class="text-center">Auto-reply</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($docs as $doc)
                                        <tr>
                                            <td style="max-width:280px;">
                                                <b>{{ $doc->title }}</b>
                                                <small class="text-muted d-block text-truncate">{{ \Illuminate\Support\Str::limit($doc->content, 90) }}</small>
                                            </td>
                                            <td><span class="badge badge-soft-info">{{ \App\Models\StoreKnowledgeDoc::typeLabel($doc->doc_type) }}</span></td>
                                            <td class="text-center">
                                                <form action="{{ route('vendor.whatsapp.knowledge.toggle') }}" method="post" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $doc->id }}">
                                                    <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent"
                                                            title="{{ $doc->active ? 'Pause — stop using for auto-reply' : 'Resume — use for auto-reply' }}">
                                                        <span class="badge badge-soft-{{ $doc->active ? 'success' : 'secondary' }}">{{ $doc->active ? 'In use' : 'Paused' }}</span>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="text-right text-nowrap">
                                                <button type="button" class="btn btn-sm btn-outline-primary kn-edit"
                                                        data-id="{{ $doc->id }}" data-type="{{ $doc->doc_type }}"
                                                        data-title="{{ $doc->title }}" data-content="{{ $doc->content }}">
                                                    <i class="tio-edit"></i>
                                                </button>
                                                <form action="{{ route('vendor.whatsapp.knowledge.delete') }}" method="post" class="d-inline"
                                                      onsubmit="return confirm('Delete this knowledge document?');">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $doc->id }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                No knowledge yet. Add your first document on the right —
                                                start with <b>Services &amp; Pricing</b> and <b>FAQs</b>.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header py-2"><h5 class="card-title mb-0">Add Knowledge</h5></div>
                    <div class="card-body">
                        <form action="{{ route('vendor.whatsapp.knowledge.store') }}" method="post">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">Document Type</label>
                                <select name="doc_type" class="form-control" required>
                                    @foreach ($docTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('doc_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">What kind of information this is — helps auto-reply pick the right document.</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" maxlength="200"
                                       placeholder="e.g. Consultation charges" value="{{ old('title') }}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Content</label>
                                <textarea name="content" class="form-control" rows="8" maxlength="20000" required
                                          placeholder="Write the information in plain language, e.g.&#10;&#10;General consultation: Rs. 300&#10;Specialist consultation: Rs. 600&#10;Open Mon–Sat, 9 AM to 8 PM. Closed on Sundays.">{{ old('content') }}</textarea>
                                <small class="text-muted">Plain text works best. One topic per document — add multiple documents instead of one long one.</small>
                            </div>
                            <button type="submit" class="btn btn--primary btn-block">Add Document</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="knEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <form action="{{ route('vendor.whatsapp.knowledge.update') }}" method="post">
                    @csrf
                    <input type="hidden" name="id" id="knId">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Knowledge</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Document Type</label>
                            <select name="doc_type" id="knType" class="form-control" required>
                                @foreach ($docTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" id="knTitle" class="form-control" maxlength="200" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Content</label>
                            <textarea name="content" id="knContent" class="form-control" rows="10" maxlength="20000" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn--primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
    $(document).on('click', '.kn-edit', function () {
        var d = $(this).data();
        $('#knId').val(d.id);
        $('#knType').val(d.type);
        $('#knTitle').val(d.title);
        $('#knContent').val(d.content);
        $('#knEditModal').modal('show');
    });
</script>
@endpush
