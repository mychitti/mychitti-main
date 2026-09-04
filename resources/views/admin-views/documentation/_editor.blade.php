{{-- CKEditor 5 wiring shared by the documentation create/edit screens. Loaded from the same
     CDN build the blog editor uses, with code blocks and headings added — SRS and API pages
     lean on those far more than a blog post does. --}}
<link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.css" />
<script type="importmap">
{
    "imports": {
        "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.js",
        "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.0.0/"
    }
}
</script>
<style>
    .ck-editor__editable_inline {
        min-height: 460px;
    }

    .doc-form-card .form-group {
        margin-bottom: 1rem;
    }

    .doc-help {
        font-size: 12px;
        color: #6c757d;
    }
</style>
<script type="module">
    import {
        ClassicEditor, Essentials, Autoformat, Bold, Italic, Underline, Strikethrough,
        Code, CodeBlock, Heading, Font, Paragraph, List, Alignment, BlockQuote, Link,
        Image, ImageToolbar, ImageCaption, ImageStyle, ImageUpload, Table, TableToolbar,
        TableProperties, TableCellProperties, SpecialCharacters, SourceEditing,
        HorizontalLine, Indent, IndentBlock, PasteFromOffice, SimpleUploadAdapter
    } from 'ckeditor5';

    ClassicEditor
        .create(document.querySelector('#doc-editor'), {
            plugins: [
                Essentials, Autoformat, Bold, Italic, Underline, Strikethrough, Code, CodeBlock,
                Heading, Font, Paragraph, List, Alignment, BlockQuote, Link, Image, ImageToolbar,
                ImageCaption, ImageStyle, ImageUpload, Table, TableToolbar, TableProperties,
                TableCellProperties, SpecialCharacters, SourceEditing, HorizontalLine, Indent,
                IndentBlock, PasteFromOffice, SimpleUploadAdapter
            ],
            toolbar: {
                items: [
                    'sourceEditing', 'undo', 'redo', '|',
                    'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', 'code', '|',
                    'fontSize', 'fontColor', 'fontBackgroundColor', '|',
                    'bulletedList', 'numberedList', 'outdent', 'indent', '|',
                    'alignment', 'blockQuote', 'codeBlock', 'horizontalLine', '|',
                    'link', 'imageUpload', 'insertTable', 'specialCharacters'
                ],
                shouldNotGroupWhenFull: true
            },
            codeBlock: {
                languages: [
                    { language: 'plaintext', label: 'Plain text' },
                    { language: 'json', label: 'JSON' },
                    { language: 'php', label: 'PHP' },
                    { language: 'javascript', label: 'JavaScript' },
                    { language: 'sql', label: 'SQL' },
                    { language: 'bash', label: 'Shell' },
                    { language: 'xml', label: 'XML / HTML' }
                ]
            },
            simpleUpload: {
                uploadUrl: '{{ route('admin.documentation.image-upload') }}',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            },
            image: {
                toolbar: ['imageTextAlternative', 'imageStyle:inline', 'imageStyle:block', 'imageStyle:side']
            },
            table: {
                contentToolbar: [
                    'tableColumn', 'tableRow', 'mergeTableCells',
                    'tableProperties', 'tableCellProperties'
                ]
            }
        })
        .then(editor => {
            // The textarea is what actually posts, so keep it in step with the editor.
            const form = document.querySelector('#doc-form');
            const textarea = document.querySelector('#doc-editor');
            if (form && textarea) {
                form.addEventListener('submit', function() {
                    textarea.value = editor.getData();
                });
            }
        })
        .catch(error => {
            console.error('Documentation editor failed to initialise.', error);
        });
</script>
