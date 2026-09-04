@extends('layouts.admin.app')

@section('title', translate('Documentation'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .doc-stat {
            border-radius: 8px;
            padding: 14px 16px;
        }

        .doc-stat h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }

        .doc-stat small {
            color: #6c757d;
        }

        .doc-cat-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .doc-cat-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .doc-title-link {
            font-weight: 600;
            color: #21325b;
        }

        .doc-meta {
            font-size: 12px;
            color: #6c757d;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center g-2">
                <div class="col-md-6 col-12">
                    <h1 class="page-header-title">
                        <span class="page-header-icon"><i class="tio-book"></i></span>
                        <span>{{ translate('Documentation') }}
                            <span class="badge badge-soft-dark ml-2">{{ $documents->total() }}</span>
                        </span>
                    </h1>
                    <p class="doc-meta mb-0">{{ translate('SRS, API references, technical designs and internal guides.') }}</p>
                </div>
                <div class="col-md-6 col-12 text-md-right">
                    <a href="{{ route('admin.documentation.categories') }}" class="btn btn-outline-secondary">
                        <i class="tio-folder"></i> {{ translate('Categories') }}
                    </a>
                    <button type="button" class="btn btn-outline-primary" data-toggle="modal"
                        data-target="#uploadDocModal">
                        <i class="tio-upload"></i> {{ translate('Upload Document') }}
                    </button>
                    <a href="{{ route('admin.documentation.create') }}" class="btn btn--primary">
                        <i class="tio-add"></i> {{ translate('New Document') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-6 col-md-4 col-xl">
                <div class="card card-body doc-stat" style="background-color:#004dff14;">
                    <h3>{{ $counts['total'] }}</h3><small>{{ translate('Documents') }}</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card card-body doc-stat" style="background-color:#6c5ce71f;">
                    <h3>{{ $counts['files'] }}</h3><small>{{ translate('Attachments') }}</small>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-2 border-0">
                <form class="row g-2 w-100 align-items-end">
                    <div class="col-md-4">
                        <div class="input-group input--group">
                            <input type="search" name="search" value="{{ $search }}" class="form-control"
                                placeholder="{{ translate('Search title, summary or tags') }}">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-control" onchange="this.form.submit()">
                            <option value="">{{ translate('All Categories') }}</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $category == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }} ({{ $cat->documents_count }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="type" class="form-control" onchange="this.form.submit()">
                            <option value="">{{ translate('All Types') }}</option>
                            @foreach (\App\Models\Documentation::DOC_TYPES as $key => $label)
                                <option value="{{ $key }}" {{ $type == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <a href="{{ route('admin.documentation.index') }}"
                            class="btn btn-outline-secondary w-100">{{ translate('Reset') }}</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive datatable-custom">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">{{ translate('SL') }}</th>
                            <th class="border-0">{{ translate('Document') }}</th>
                            <th class="border-0">{{ translate('Category') }}</th>
                            <th class="border-0">{{ translate('Type') }}</th>
                            <th class="border-0 text-center">{{ translate('Version') }}</th>
                            <th class="border-0 text-center">{{ translate('Files') }}</th>
                            <th class="border-0">{{ translate('Updated') }}</th>
                            <th class="border-0 text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $key => $doc)
                            <tr>
                                <td>{{ $key + $documents->firstItem() }}</td>
                                <td>
                                    <a href="{{ route('admin.documentation.show', $doc->id) }}" class="doc-title-link">
                                        {{ $doc->title }}
                                    </a>
                                    @if ($doc->summary)
                                        <div class="doc-meta">{{ Str::limit($doc->summary, 70) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($doc->category)
                                        <span class="doc-cat-pill"
                                            style="background: {{ $doc->category->color ?? '#6c5ce7' }}1f; color: {{ $doc->category->color ?? '#6c5ce7' }};">
                                            <span class="doc-cat-dot"
                                                style="background: {{ $doc->category->color ?? '#6c5ce7' }};"></span>
                                            {{ $doc->category->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ \App\Models\Documentation::DOC_TYPES[$doc->doc_type] ?? $doc->doc_type }}</td>
                                <td class="text-center">v{{ $doc->version }}</td>
                                <td class="text-center">{{ $doc->files_count }}</td>
                                <td class="doc-meta">{{ $doc->updated_at?->format('d M Y') }}</td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                            href="{{ route('admin.documentation.show', $doc->id) }}"
                                            title="{{ translate('View') }}"><i class="tio-visible"></i></a>
                                        <a class="btn action-btn btn--warning btn-outline-warning"
                                            href="{{ route('admin.documentation.edit', $doc->id) }}"
                                            title="{{ translate('Edit') }}"><i class="tio-edit"></i></a>
                                        <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                            href="javascript:" data-id="doc-{{ $doc->id }}"
                                            data-message="{{ translate('Want to delete this document and all its files?') }}"
                                            title="{{ translate('Delete') }}"><i class="tio-delete-outlined"></i></a>
                                        <form action="{{ route('admin.documentation.delete', $doc->id) }}" method="post"
                                            id="doc-{{ $doc->id }}">
                                            @csrf @method('delete')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <p class="mb-2">{{ translate('No documents yet.') }}</p>
                                    <a href="{{ route('admin.documentation.create') }}"
                                        class="btn btn--primary btn-sm">{{ translate('Create the first one') }}</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (count($documents) !== 0)
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-center justify-content-sm-end">
                        {!! $documents->links() !!}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="uploadDocModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('admin.documentation.upload-document') }}" method="post"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Upload Document') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="input-label">{{ translate('File') }} <span class="text-danger">*</span></label>
                            <input type="file" name="files[]" class="form-control" multiple required
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.txt,.md,.json,.zip,.png,.jpg,.jpeg,.gif,.webp">
                            <div class="doc-meta mt-1">
                                {{ translate('Word, PDF, Excel, PowerPoint, TXT/MD, JSON, ZIP or images. Up to 25 MB each — several files can go into one document.') }}
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Title') }}</label>
                            <input type="text" name="title" class="form-control"
                                placeholder="{{ translate('Leave blank to use the file name') }}">
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Category') }}</label>
                                <select name="category_id" class="form-control doc-category-select">
                                    <option value="">{{ translate('Uncategorised') }}</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="input-label">{{ translate('Version') }}</label>
                                <input type="text" name="version" class="form-control" placeholder="1.0">
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="input-label">{{ translate('Summary') }}</label>
                            <textarea name="summary" rows="2" class="form-control"
                                placeholder="{{ translate('One line on what this document covers') }}"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('Upload') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        // Category is a tag box: pick an existing one or type a new name, which the controller
        // creates on save. dropdownParent keeps the search field usable inside a modal.
        $(function() {
            $('.doc-category-select').each(function() {
                var $el = $(this);
                var $modal = $el.closest('.modal');
                $el.select2({
                    tags: true,
                    width: '100%',
                    placeholder: '{{ translate('Select or type a category') }}',
                    dropdownParent: $modal.length ? $modal : $(document.body)
                });
            });
        });
    </script>
@endpush
