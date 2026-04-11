@extends('layouts.vendor.app')

@section('title', 'Edit Blog Post')

@push('css_or_js')
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.css" />
    <script type="importmap">
    {
        "imports": {
            "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.js",
            "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.0.0/"
        }
    }
    </script>
    <script type="module">
        import {
            ClassicEditor, Essentials, Bold, Italic, Underline, Subscript, Superscript,
            Font, Paragraph, Alignment, BlockQuote, Link, Image, ImageToolbar, ImageCaption,
            ImageStyle, ImageUpload, Table, TableToolbar, SpecialCharacters, SourceEditing, SimpleUploadAdapter
        } from 'ckeditor5';

        let editorInstance;

        ClassicEditor.create(document.querySelector('#editor'), {
            plugins: [
                Essentials, Bold, Italic, Underline, Subscript, Superscript, Font, Paragraph,
                Alignment, BlockQuote, Link, Image, ImageToolbar, ImageCaption, ImageStyle,
                ImageUpload, Table, TableToolbar, SpecialCharacters, SourceEditing, SimpleUploadAdapter
            ],
            toolbar: {
                items: [
                    'sourceEditing', 'undo', 'redo', '|', 'bold', 'italic', 'underline', '|',
                    'fontSize', 'fontColor', '|',
                    'alignment:left', 'alignment:center', 'alignment:right', 'alignment:justify', '|',
                    'blockquote', 'link', 'imageUpload', '|', 'insertTable', '|', 'specialCharacters'
                ],
                shouldNotGroupWhenFull: true
            },
            simpleUpload: {
                uploadUrl: '{{ route('vendor.blog.image-upload') }}',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            },
            image: { toolbar: ['imageTextAlternative', 'imageStyle:inline', 'imageStyle:block', 'imageStyle:side'] },
            table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'] }
        })
        .then(editor => { editorInstance = editor; })
        .catch(error => { console.error(error); });

        document.querySelector('#blog_form').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!editorInstance) return;

            const content = editorInstance.getData();
            if (!content.trim()) {
                toastr.error('Description is required.');
                return;
            }

            document.querySelector('#description_hidden').value = content;
            this.submit();
        });
    </script>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon"><img src="{{ asset('public/assets/admin/img/edit.png') }}" class="w--20" alt=""></span>
                <span>Edit Blog Post</span>
            </h1>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('vendor.blog.update', $blog->id) }}" id="blog_form" method="post" enctype="multipart/form-data">
                    @csrf @method('POST')

                    <div class="row">
                        <div class="col-md-8">
                            {{-- Website / Type --}}
                            <div class="form-group">
                                <label class="input-label">Publish On <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <div>
                                        <input type="radio" name="type" id="type_common" value="common" {{ $blog->type === 'common' ? 'checked' : '' }}>
                                        <label for="type_common">mychitti.net</label>
                                    </div>
                                    <div>
                                        <input type="radio" name="type" id="type_mc_vendor" value="mc_vendor" {{ $blog->type === 'mc_vendor' ? 'checked' : '' }}>
                                        <label for="type_mc_vendor">mcvendorhub.com</label>
                                    </div>
                                </div>
                            </div>

                            {{-- Category --}}
                            <div class="form-group">
                                <label class="input-label">Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_select" class="form-control js-select2-custom">
                                    <option value="" disabled>Select Category</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ $cat->id == $blog->category_id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Title --}}
                            <div class="form-group">
                                <label class="input-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="Blog title" maxlength="191" value="{{ old('title', $blog->title) }}" required>
                            </div>

                            {{-- Short Description --}}
                            <div class="form-group">
                                <label class="input-label">Short Description</label>
                                <textarea class="form-control" name="short_description" rows="2" placeholder="Short description (optional)">{{ old('short_description', $blog->short_description) }}</textarea>
                            </div>

                            {{-- Description --}}
                            <div class="form-group">
                                <label class="input-label">Description <span class="text-danger">*</span></label>
                                <textarea id="editor">{{ $blog->description }}</textarea>
                                <input type="hidden" name="description" id="description_hidden">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="h-100 d-flex align-items-center flex-column">
                                <label class="mb-3 text-center">Cover Image <small class="text-danger">(ratio 1:1)</small></label>
                                <label class="text-center my-auto position-relative d-inline-block">
                                    <img class="img--176 border" id="viewer"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($blog->image, asset('storage/app/public/blog') . '/' . $blog->image, asset('public/assets/admin/img/upload-img.png'), 'blog/') }}"
                                        alt="image">
                                    <div class="icon-file-group">
                                        <div class="icon-file">
                                            <input type="file" name="image" id="imageInput" class="custom-file-input read-url" accept=".jpg,.png,.jpeg,.gif,.bmp,.tiff|image/*">
                                            <i class="tio-edit"></i>
                                        </div>
                                    </div>
                                </label>
                                <small class="text-muted mt-2">Leave blank to keep current image</small>
                            </div>
                        </div>
                    </div>

                    @if($blog->status == 0)
                        <div class="alert alert-warning mt-3">
                            <i class="tio-info-outined"></i> This post is <strong>pending approval</strong>. Saving will re-submit it for review.
                        </div>
                    @endif

                    <div class="btn--container justify-content-end mt-3">
                        <a href="{{ route('vendor.blog.index') }}" class="btn btn--reset">Cancel</a>
                        <button type="submit" class="btn btn--primary">Save & Re-submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
$(document).ready(function () {
    function loadCategories(type, selectedId) {
        $.get('{{ route('vendor.blog.categories-by-type') }}', { type: type }, function (res) {
            var select = $('#category_select');
            select.empty().append('<option value="" disabled>Select Category</option>');
            res.forEach(function (cat) {
                var sel = selectedId && cat.id == selectedId ? 'selected' : '';
                select.append('<option value="' + cat.id + '" ' + sel + '>' + cat.text + '</option>');
            });
        });
    }

    // Load categories for current type, preserving selection
    loadCategories($('input[name=type]:checked').val(), {{ $blog->category_id }});

    $('input[name=type]').on('change', function () {
        loadCategories($(this).val(), null);
    });

    $('#imageInput').change(function () {
        readURL(this);
        $('#viewer').show(1000);
    });
});
</script>
@endpush
