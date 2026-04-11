@extends('layouts.admin.app')

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
        uploadUrl: '{{ route('admin.blog.image-upload') }}',
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

$('#blog_form').on('submit', function(e) {
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
        url: '{{ route('admin.blog.update') }}',
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

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/edit.png')}}" class="w--20" alt="">
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="card"> 
            <div class="card-body">
                <form action="{{route('admin.blog.update')}}" id="blog_form" method="post" enctype="multipart/form-data">
                    @csrf 
                    <div class="row">
                        <input type="hidden" name="id" value="{{$post->id}}">
                        <div class="col-md-8 row">
                            <input type="hidden" name="type" value="{{ $post->type }}">
                                <div class="form-group lang_form col-md-6" id="default-form">
                                    <label class="input-label" for="exampleFormControlInput1">Category <span class="form-label-secondary text-danger"
                                        data-toggle="tooltip" data-placement="right"
                                        data-original-title="{{ translate('messages.Required.')}}"> *
                                        </span>
                                    </label>
                                 <select name="category" id="" class="form-control js-select2-custom">
                                     <option value="" disabled selected>Select Category</option>
                                    @foreach($categories as $cat)
                                    <option {{ $cat->id == $post->category_id ? 'selected' : ''}} value="{{$cat->id}}">{{$cat->name}}</option>
                                    @endforeach
                                 </select>
                                </div>
                                <div class="form-group lang_form col-12" id="default-form">
                                    <label class="input-label" for="exampleFormControlInput1">Title <span class="form-label-secondary text-danger"
                                        data-toggle="tooltip" data-placement="right"
                                        data-original-title="{{ translate('messages.Required.')}}"> *
                                        </span>
                                    </label>
                                    <input  type="text" name="name" class="form-control" placeholder="Title" maxlength="191" value="{{$post->title}}"  >
                                </div>
                                <div class="form-group lang_form col-12"  id="default-form2">
                                    <label class="input-label" for="exampleFormControlInput2">Short Description<span class="form-label-secondary text-danger"
                                        >
                                        </span>
                                    </label>
                                    <textarea placeholder="Short Description" class="form-control" name="short_description" id="">{{$post->short_description}}</textarea>
                                </div>
                                <div class="form-group lang_form col-12 " id="default-form2">
                                    <label class="input-label" for="exampleFormControlInput2">Description<span class="form-label-secondary text-danger"
                                        data-toggle="tooltip" data-placement="right"
                                        data-original-title="{{ translate('messages.Required.')}}"> *
                                        </span>
                                    </label>
                                    <textarea name="description" id="editor">{{$post->description}}</textarea>
                                </div>
                        </div>
                        <div class="col-md-4">
                            <div class="h-100 d-flex align-items-center flex-column">
                                <label class="mb-3 text-center">{{translate('messages.image')}} <small class="text-danger">* ( {{translate('messages.ratio')}} 1:1)</small></label>
                                <label class="text-center my-auto position-relative d-inline-block">
                                    <img class="img--176 border" id="viewer"
                                     src="{{\App\CentralLogics\Helpers::onerror_image_helper($post->image, asset('storage/app/public/blog/').'/'.$post->image, asset('public/assets/admin/img/upload-img.png'), 'blog/') }}"
                                        data-onerror-image="{{asset('public/assets/admin/img/upload-img.png')}}"
                                        alt="image"/>
                                    <div class="icon-file-group">
                                        <div class="icon-file">
                                            <input type="file" name="image" id="customFileEg1" class="custom-file-input read-url"
                                                accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" >
                                                <i class="tio-edit"></i>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="btn--container justify-content-end mt-3">
                        <button type="reset" id="reset_btn" class="btn btn--reset">{{translate('messages.reset')}}</button>
                        <button type="submit" class="btn btn--primary">Submit</button>
                    </div>
                </form>
            </div>
            <!-- End Table -->
        </div>
    </div>

@endsection

@push('script_2')
<script>
  $(document).ready(function() {
         
            function loadCategories(type) {
                $.ajax({
                    url: "{{route('admin.blog.category-select_type')}}", // <-- your backend route
                    type: "GET",
                    data: {
                        type: type
                    },
                    success: function(res) {
                        $('#category_select').empty();
                        $('#category_select').select2({
                            data: res,
                            placeholder: "Select category",
                            allowClear: true
                        });
                    },
                    error: function() {
                    }
                });
            }

            loadCategories($("input[name=type]:checked").val());

            $("input[name=type]").on("change", function() {
                var type = $(this).val();
                loadCategories(type);
            });
        });
        $(document).on('change','.warnig-charge-btn', function (e){
        
          $('.warnig-charge-btn').prop('checked', false);
          swal({
               //text: message,
                title: 'Please Add Lead Charges First',
                type: 'warning',
                confirmButtonColor: '#FC6A57',
                confirmButtonText: 'Add Now',
            }).then(function(isConfirm) {
                console.log(isConfirm)
              if (isConfirm.value) {
                  window.location.href = '{{route('admin.service.lead-charge')}}';
              } else {
                 
               
              }
            })
          
        })
    </script>
    
    <script src="{{asset('public/assets/admin')}}/js/view-pages/category-index.js"></script>
    <script>
    "use strict";
        $('.location-reload-to-category').on('click', function() {
            const url = $(this).data('url');
            let nurl = new URL(url);
            nurl.searchParams.delete('search');
            location.href = nurl;
        });

        $("#customFileEg1").change(function() {
            readURL(this);
            $('#viewer').show(1000)
        });
        $('#reset_btn').click(function(){
            $('#exampleFormControlSelect1').val(null).trigger('change');
                $('#viewer').attr('src', "{{asset('public/assets/admin/img/upload-img.png')}}");
        })
    </script>

@endpush
