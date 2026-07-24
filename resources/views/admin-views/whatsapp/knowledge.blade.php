@extends('layouts.admin.app')

@section('title', translate('WhatsApp Auto-Reply Knowledge'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title"><i class="tio-book-opened"></i> {{ translate('WhatsApp Auto-Reply Knowledge') }}</h1>
            <div class="d-flex align-items-center" style="gap:8px;">
                <a href="{{ route('admin.business-settings.third-party.whatsapp-inbox') }}" class="btn btn-sm btn-outline-primary">
                    <i class="tio-chat"></i> {{ translate('Chats') }}
                </a>
                <form method="get" class="mb-0">
                    <select name="type" class="form-control form-control-sm" style="width:auto;" onchange="this.form.submit()">
                        <option value="">{{ translate('All types') }}</option>
                        @foreach ($docTypes as $key => $label)
                            <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        <p class="text-muted" style="font-size:13px;">
            {{ translate('Teach the MyChitti WhatsApp assistant. When a vendor or customer messages the MyChitti number, answers come from your active documents here.') }}
        </p>

        <div class="row">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header py-2"><h5 class="card-title mb-0">{{ translate('Knowledge') }} ({{ $docs->count() }})</h5></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-align-middle mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>{{ translate('Document') }}</th>
                                        <th>{{ translate('Type') }}</th>
                                        <th class="text-center">{{ translate('Auto-reply') }}</th>
                                        <th class="text-right">{{ translate('Action') }}</th>
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
                                                <form action="{{ route('admin.business-settings.third-party.whatsapp-knowledge.toggle') }}" method="post" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $doc->id }}">
                                                    <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent"
                                                            title="{{ $doc->active ? translate('Pause') : translate('Resume') }}">
                                                        <span class="badge badge-soft-{{ $doc->active ? 'success' : 'secondary' }}">{{ $doc->active ? translate('In use') : translate('Paused') }}</span>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="text-right text-nowrap">
                                                <button type="button" class="btn btn-sm btn-outline-primary kn-edit"
                                                        data-id="{{ $doc->id }}" data-type="{{ $doc->doc_type }}"
                                                        data-title="{{ $doc->title }}" data-content="{{ $doc->content }}">
                                                    <i class="tio-edit"></i>
                                                </button>
                                                <form action="{{ route('admin.business-settings.third-party.whatsapp-knowledge.delete') }}" method="post" class="d-inline"
                                                      onsubmit="return confirm('{{ translate('Delete this document?') }}');">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $doc->id }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                {{ translate('No knowledge yet. Add your first document on the right.') }}
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
                    <div class="card-header py-2"><h5 class="card-title mb-0">{{ translate('Add Knowledge') }}</h5></div>
                    <div class="card-body">
                        <form action="{{ route('admin.business-settings.third-party.whatsapp-knowledge.store') }}" method="post">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">{{ translate('Document Type') }}</label>
                                <select name="doc_type" class="form-control" required>
                                    @foreach ($docTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('doc_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Title') }}</label>
                                <input type="text" name="title" class="form-control" maxlength="200"
                                       placeholder="{{ translate('e.g. How to connect WhatsApp') }}" value="{{ old('title') }}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Content') }}</label>
                                <textarea name="content" class="form-control" rows="8" maxlength="20000" required
                                          placeholder="{{ translate('Write the information in plain language. One topic per document.') }}">{{ old('content') }}</textarea>
                                <small class="text-muted">{{ translate('Plain text works best. Add multiple documents instead of one long one.') }}</small>
                            </div>
                            <button type="submit" class="btn btn--primary btn-block">{{ translate('Add Document') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="knEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.business-settings.third-party.whatsapp-knowledge.update') }}" method="post">
                    @csrf
                    <input type="hidden" name="id" id="knId">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Edit Knowledge') }}</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Document Type') }}</label>
                            <select name="doc_type" id="knType" class="form-control" required>
                                @foreach ($docTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Title') }}</label>
                            <input type="text" name="title" id="knTitle" class="form-control" maxlength="200" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Content') }}</label>
                            <textarea name="content" id="knContent" class="form-control" rows="10" maxlength="20000" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white" data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('Save Changes') }}</button>
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
