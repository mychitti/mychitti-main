<style>
    .ck-editor__editable {
        min-height: 100px;
        /* adjust as you like */
    }
</style>
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
        ClassicEditor,
        Essentials,
        Bold,
        Italic,
        Underline,
        Font,
        Paragraph,
        Alignment,
        BlockQuote,
        Link,
        Table,
        TableToolbar,
        SpecialCharacters,
        SourceEditing,
        SimpleUploadAdapter,  
         Heading  
    } from 'ckeditor5';

    const config = {
        plugins: [
            Essentials, Bold, Italic, Underline, Font,
            Paragraph, Heading, Alignment, BlockQuote, Link, Table, TableToolbar,
            SpecialCharacters, SourceEditing, SimpleUploadAdapter
        ],
        toolbar: {
            items: [
                'heading', '|','undo', 'redo', '|',
                'bold', 'italic', 'underline',
                'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                'alignment:left', 'alignment:right', 'alignment:center', 'alignment:justify', '|',
                'link', 'unlink', '|',
                'insertTable', 'mergeTableCells'
            ],
            shouldNotGroupWhenFull: true
        },
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
                { model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },
                { model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6' }
            ]
        },
        table: {
            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
        }
    };

    window.ckeditors = {};
    window.ckeditors2 = {};

    document.querySelectorAll('.ck_editor').forEach(el => {
        ClassicEditor.create(el, config)
            .then(editor => {
                window.ckeditors[el.id] = editor;
            })
            .catch(error => console.error('Editor init error:', error));
    });
    document.querySelectorAll('.ck_editor2').forEach(el => {
        ClassicEditor.create(el, config)
            .then(editor => {
                window.ckeditors2[el.id] = editor;
            })
            .catch(error => console.error('Editor init error:', error));
    });
    document.querySelectorAll('#maineditor').forEach(el => {
        ClassicEditor.create(el, config)
            .then(editor => {
                window.ckeditors[el.id] = editor;
            })
            .catch(error => console.error('Editor init error:', error));
    });

    function base64EncodeUnicode(str) {
        let b64 = btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
            function(match, p1) {
                return String.fromCharCode('0x' + p1);
            }
        ));
        // Convert to URL-safe base64
        return b64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }



    $('#ck_editor_form').on('submit', function(e) {
        e.preventDefault();
        $('#formSubmitButton').attr('disabled', true);

        let formData = new FormData(this);

        document.querySelectorAll('.ck_editor').forEach(editorElement => {
            let editor = window.ckeditors[editorElement.id];
            if (editor) {
                formData.set(editorElement.name, base64EncodeUnicode(editor.getData()));
            }
        });

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            success: function(data) {
                toastr.success("Saved successfully!");
                if (data.url) {
                    setTimeout(() => {
                        $('#formSubmitButton').attr('disabled', false);
                        window.location.href = data.url
                    }, 1000)
                }
            },
            error: function(xhr) {
                $('#formSubmitButton').attr('disabled', false);
                toastr.error('Something went wrong!');
                console.error(xhr.responseText);
            }
        });
    });
    $('#ck_editor_form2').on('submit', function(e) {
        e.preventDefault();
        $('#formSubmitButton').attr('disabled', true);

        let formData = new FormData(this);

        document.querySelectorAll('.ck_editor2').forEach(editorElement => {
            let editor = window.ckeditors2[editorElement.id];
            if (editor) {
                formData.set(editorElement.name, base64EncodeUnicode(editor.getData()));
            }
        });

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            success: function(data) {
                toastr.success("Saved successfully!");
                if (data.url) { 
                    setTimeout(() => {
                        $('#formSubmitButton').attr('disabled', false);
                        window.location.href = data.url
                    }, 1000)
                }
                if (data.status) { 
                                   toastr.success(data.message);

                }
            },
            error: function(xhr) {
                $('#formSubmitButton').attr('disabled', false);
                toastr.error('Something went wrong!');
                console.error(xhr.responseText);
            }
        });
    });
    @if (in_array(Route::currentRouteName(), ['admin.item.add-new', 'admin.item.edit']))
        @include('admin-views.js.service_seo_settings_js')
    @endif
</script>
