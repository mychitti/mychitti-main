
<link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.css" />
    <script type="importmap">
    {
        "imports": {
            "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.js",
            "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.0.0/"
        }
    }
</script>
<script type="importmap">
    {
        "imports": {
            "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.js",
            "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.0.0/"
        }
    }
</script>
<script type="module">
import { ClassicEditor, 
    Essentials, 
    Bold, 
    Italic, 
    Underline, 
    Subscript, 
    Superscript, 
    Font, 
    Paragraph, 
    Alignment, 
    BlockQuote, 
    Link, 
    Image, 
    ImageToolbar, 
    ImageCaption, 
    ImageStyle, 
    ImageUpload, 
    Table, 
    TableToolbar, 
    SpecialCharacters, 
    SourceEditing,
    SimpleUploadAdapter 
} from 'ckeditor5';

let editorInstance;

ClassicEditor
.create(document.querySelector('#editor'), {
    plugins: [ 
        Essentials, 
        Bold, 
        Italic, 
        Underline, 
        Subscript, 
        Superscript, 
        Font, 
        Paragraph, 
        Alignment, 
        BlockQuote, 
        Link, 
        Image, 
        ImageToolbar, 
        ImageCaption, 
        ImageStyle, 
        ImageUpload, 
        Table, 
        TableToolbar, 
        SpecialCharacters, 
        SourceEditing,
        SimpleUploadAdapter
    ],
    toolbar: {
        items: [
            'sourceEditing', 
            'undo', 
            'redo', 
            '|', 
            'cut', 
            'copy', 
            'paste', 
            '|', 
            'bold', 
            'italic', 
            'underline', 
            'subscript', 
            'superscript', 
            '|', 
            'fontSize', 
            'fontFamily', 
            'fontColor', 
            'fontBackgroundColor', 
            '|', 
            'alignment:left', 
            'alignment:right', 
            'alignment:center', 
            'alignment:justify', 
            '|', 
            'blockquote', 
            'link', 
            'unlink', 
            'imageUpload', 
            '|', 
            'insertTable', 
            'tableColumn', 
            'tableRow', 
            'mergeTableCells', 
            '|', 
            'specialCharacters', 
            'fullscreen'
        ],
        shouldNotGroupWhenFull: true
    },
    simpleUpload: {
        uploadUrl: $(".upload_url").val(),
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        transformUrl: (url) => {
        return url.replace('https://mychitti.netstorage', 'https://mychitti.net/storage');
    }
    },
    image: {
        toolbar: [
            'imageTextAlternative', 
            'imageStyle:inline', 
            'imageStyle:block', 
            'imageStyle:side'
        ]
    },
    table: {
        contentToolbar: [
            'tableColumn', 
            'tableRow', 
            'mergeTableCells'
        ]
    }
})
.then(editor => {
    editorInstance = editor;
})
.catch(error => {
    console.error('There was a problem initializing the editor.', error);
});

$('#ck_editor_form').on('submit', function(e) {
    e.preventDefault();
    $('#submitButton').attr('disabled', true);

    if (!editorInstance) {
        console.error('CKEditor instance is not available.');
        return;
    }

    let formData = new FormData(this);
    formData.append('description', editorInstance.getData());

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.ajax({
        url: '{{ route('admin.blog.save') }}',
        type: 'POST',
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        beforeSend: function() {
            $('#loading').show();
        },
        success: function(data) {
            $('#loading').hide();
            if (data.errors) {
                data.errors.forEach(error => {
                    toastr.error(error.message || error, {
                        CloseButton: true, 
                        ProgressBar: true 
                    });
                });
                $('#submitButton').attr('disabled', false);
            } else {
                toastr.success(data.msg, {
                    CloseButton: true, 
                    ProgressBar: true 
                });
                setTimeout(function() {
                    {{-- window.location.href = data.redirect; --}}
                }, 1000);
            }
        },
        error: function(xhr) {
            $('#loading').hide();
            $('#submitButton').attr('disabled', false);
            
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                Object.values(xhr.responseJSON.errors).forEach(errorArray => {
                    errorArray.forEach(error => {
                        toastr.error(error, {
                            CloseButton: true, 
                            ProgressBar: true 
                        });
                    });
                });
            } else {
                toastr.error('An unexpected error occurred', {
                    CloseButton: true, 
                    ProgressBar: true 
                });
            }
        }
    });
});
</script>
