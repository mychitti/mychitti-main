@php $doc = $doc ?? null; @endphp

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card doc-form-card mb-3">
            <div class="card-body">
                <div class="form-group">
                    <label class="input-label">{{ translate('Title') }} <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required
                        value="{{ old('title', $doc->title ?? '') }}"
                        placeholder="{{ translate('e.g. MyChitti HMIS — Software Requirement Specification') }}">
                </div>

                <div class="form-group">
                    <label class="input-label">{{ translate('Summary') }}</label>
                    <textarea name="summary" rows="2" class="form-control"
                        placeholder="{{ translate('One or two lines describing what this document covers') }}">{{ old('summary', $doc->summary ?? '') }}</textarea>
                </div>

                <div class="form-group mb-0">
                    <label class="input-label">{{ translate('Document Body') }}</label>
                    <textarea name="content" id="doc-editor">{{ old('content', $doc->content ?? '') }}</textarea>
                    <div class="doc-help mt-1">
                        {{ translate('Leave this empty if the document is only an uploaded file. Use the code-block button for JSON payloads and SQL.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card doc-form-card mb-3">
            <div class="card-header border-0 py-3">
                <h5 class="card-title mb-0">{{ translate('Details') }}</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="input-label">{{ translate('Category') }}</label>
                    <select name="category_id" class="form-control doc-category-select">
                        <option value="">{{ translate('Uncategorised') }}</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}"
                                {{ old('category_id', $doc->category_id ?? '') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="doc-help mt-1">
                        <a href="{{ route('admin.documentation.categories') }}"
                            target="_blank">{{ translate('Manage categories') }}</a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label">{{ translate('Type') }} <span class="text-danger">*</span></label>
                    <select name="doc_type" class="form-control" required>
                        @foreach (\App\Models\Documentation::DOC_TYPES as $key => $label)
                            <option value="{{ $key }}"
                                {{ old('doc_type', $doc->doc_type ?? 'editor') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <div class="doc-help mt-1">
                        {{ translate('API endpoints are their own section — this is for SRS, write-ups and files.') }}
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label">{{ translate('Version') }}</label>
                    <input type="text" name="version" class="form-control"
                        value="{{ old('version', $doc->version ?? '1.0') }}" placeholder="1.0">
                </div>

                <div class="form-group mb-0">
                    <label class="input-label">{{ translate('Tags') }}</label>
                    <input type="text" name="tags" class="form-control" value="{{ old('tags', $doc->tags ?? '') }}"
                        placeholder="{{ translate('hmis, billing, v2') }}">
                    <div class="doc-help mt-1">{{ translate('Comma separated. Searchable from the list screen.') }}</div>
                </div>
            </div>
        </div>

        <div class="card doc-form-card mb-3">
            <div class="card-header border-0 py-3">
                <h5 class="card-title mb-0">{{ translate('Attachments') }}</h5>
            </div>
            <div class="card-body">
                <div class="form-group mb-0">
                    <input type="file" name="files[]" class="form-control" multiple
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.txt,.md,.json,.zip,.png,.jpg,.jpeg,.gif,.webp">
                    <div class="doc-help mt-1">
                        {{ translate('Word, PDF, Excel, PowerPoint, JSON, ZIP or images. Up to 25 MB each, multiple files allowed. Every upload is kept in version history.') }}
                    </div>
                </div>
            </div>
        </div>

        @if ($doc)
            <div class="card doc-form-card mb-3">
                <div class="card-body">
                    <div class="form-group mb-0">
                        <label class="input-label">{{ translate('Version note') }}</label>
                        <input type="text" name="version_note" class="form-control"
                            placeholder="{{ translate('What changed in this edit?') }}">
                        <div class="doc-help mt-1">
                            {{ translate('Saved against the copy being replaced, so the history reads as a changelog.') }}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ $doc ? route('admin.documentation.show', $doc->id) : route('admin.documentation.index') }}"
                class="btn btn-secondary mr-2">{{ translate('Cancel') }}</a>
            <button type="submit" class="btn btn--primary">
                {{ $doc ? translate('Save Changes') : translate('Create Document') }}
            </button>
        </div>
    </div>
</div>

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
