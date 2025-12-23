@extends('layouts.admin.app')

@section('title',translate('messages.Update blog category'))

@push('css_or_js')

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
                <form action="{{route('admin.blog.category-update',[$category['id']])}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                                <div class="form-group lang_form" id="default-form">
                                    <label class="input-label" for="exampleFormControlInput1">{{translate('messages.name')}} ({{ translate('messages.default') }}) <span class="form-label-secondary text-danger"
                                        data-toggle="tooltip" data-placement="right"
                                        data-original-title="{{ translate('messages.Required.')}}"> *
                                        </span>
                                    </label>
                                    <input type="text" name="name" class="form-control" placeholder="{{translate('messages.new_category')}}" maxlength="191" value="{{$category?->getRawOriginal('name')}}"  >
                                </div>
                        </div>
                         <div class="col-md-4">

                            <div class="form-group ">
                                <label class="input-label" for="exampleFormControlInput1">Type


                                </label>
                                <div class="d-flex gap-2">
                                    <div>
                                        <input type="radio" name="type" id="common" value="common" class=""
                                        {{$category->type=='common'?'checked':''}}>
                                        <label class="form-label" for="common">Common</label>
                                    </div>
                                    <div>
                                        <input type="radio" name="type" id="mc_vendor" value="mc_vendor"
                                           {{$category->type=='mc_vendor'?'checked':''}}  class="">
                                        <label class="form-label" for="mc_vendor">MC Vendor</label>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-md-4">
                            <div class="h-100 d-flex align-items-center flex-column">
                                <label class="mb-4">{{translate('messages.image')}}
                                    <small class="text-danger">* ( {{translate('messages.ratio')}} 1:1 )</small>
                                </label>
                                <label class="text-center my-auto position-relative d-inline-block">
                                    <img class="img--176 border" id="viewer"
                                    src="{{\App\CentralLogics\Helpers::onerror_image_helper($category['image'], asset('storage/app/public/blog/category/').'/'.$category['image'], asset('public/assets/admin/img/upload-img.png'), 'blog/category/') }}"
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
@endpush
