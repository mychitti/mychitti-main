@extends('layouts.admin.app')

@section('title',translate('messages.Update category'))

@if($category->position ==  1)
@section('sub_category')
@else
@section('main_category')
@endif
active
@endsection

@push('css_or_js')
<style>
    .select2-selection__choice {
    padding: 2px 5px;
    font-size: 14px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove{
    border-right: none !important;
}
.select2-selection .select2-selection--multiple{
    border: 1px solid #e6e6e6;
    padding: 4px !important;
}
.select2-container--default.select2-container--focus .select2-selection--multiple{
    border: 1px solid #e6e6e6;
    padding: 4px !important;
}
.select2-container--default .select2-selection--multiple{
    border: 1px solid #dedede !important;
}
.select2-container--default.select2-container--focus .select2-selection--multiple{
    border: 1px solid #dedede !important;
}
.select2-selection {
    height: 200px !important;
}

</style>

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/edit.png')}}" class="w--20" alt="">
                </span>
                <span>
                    {{$category->position?translate('messages.sub').' ':''}}{{translate('messages.category_update')}}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="card">
            <div class="card-body">
                <form action="{{route('admin.category.update',[$category['id']])}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                          
                                <div class="form-group">
                                    <label class="input-label" for="exampleFormControlInput1">{{translate('messages.name')}}</label>
                                    <input type="text" name="name[]" class="form-control" placeholder="{{translate('messages.new_category')}}" value="{{$category['name']}}" maxlength="191">
                                </div>
                                <div class="form-group">
                                    <label class="input-label" for="exampleFormControlInput1">Keywords</label>
                                    <select class="js-example-tags" name="keywords[]" multiple="multiple " value="{{$category['keywords']}}">
                                        @foreach(explode(',', $category['keywords']) as $key)
                                         <option value="{{$key}}" selected>{{$key}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <input type="hidden" name="lang[]" value="default">
                    

                        </div>
                        <div class="col-md-6">
                            @if ($category->position == 0)     @endif
                            <div class="h-100 d-flex align-items-center flex-column"> 
                                <label class="mb-4">{{translate('messages.image')}}
                                    <small class="text-danger">* ( {{translate('messages.ratio')}} 1:1 )</small>
                                </label>
                                <label class="text-center my-auto position-relative d-inline-block">
                                    <img class="img--176 border" id="viewer"
                                    src="{{\App\CentralLogics\Helpers::onerror_image_helper($category['image'], asset('storage/app/public/category/').'/'.$category['image'], asset('public/assets/admin/img/upload-img.png'), 'category/') }}"
                                        data-onerror-image="{{asset('public/assets/admin/img/upload-img.png')}}"
                                        alt=""/>
                                    <div class="icon-file-group">
                                        <div class="icon-file">
                                            <input type="file" name="image" id="customFileEg1" class="custom-file-input read-url"
                                                accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                <i class="tio-edit"></i>
                                        </div>
                                    </div>
                                </label>
                            </div>
                       
                        </div>
                    </div>
                    <div class="btn--container justify-content-end mt-3">
                        <button type="reset" id="reset_btn" class="btn btn--reset">{{translate('messages.reset')}}</button>
                        <button type="submit" class="btn btn--primary">{{translate('messages.update')}}</button>
                    </div>
                </form>
            </div>
            <!-- End Table -->
        </div>
    </div>

@endsection

@push('script_2')
    <script src="{{asset('public/assets/admin')}}/js/view-pages/category-index.js"></script>
    <script>
        "use strict";
        $('#reset_btn').click(function(){
            $('#module_id').val("{{ $category->module_id }}").trigger('change');
            $('#viewer').attr('src', "{{\App\CentralLogics\Helpers::onerror_image_helper($category['image'], asset('storage/app/public/category/').'/'.$category['image'], asset('public/assets/admin/img/upload-img.png'), 'category/') }}");
        })
        $("#customFileEg1").change(function() {
            readURL(this);
            $('#viewer').show(1000)
        });



    </script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
        $('.js-example-tags').select2({
            tags: true,
            tokenSeparators: [','],
            placeholder: "Start Typing..",
            allowClear: true,
        });
    });
    </script>
@endpush
