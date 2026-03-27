@extends('layouts.admin.app')

@section('title',translate('Update Banner'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/edit.png')}}" class="w--26" alt="">
                </span>
                <span> 
                    {{translate('messages.update_banner')}}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.banner.update', [$banner->id])}}" method="post"
                            id="banner_form"
                            >
                            @csrf
                            <div class="row g-3">
                                <div class="col-lg-8 row">

                                    @if($language)
                                        <div class="col-md-12">
                                            <ul class="nav nav-tabs mb-4">
                                                <li class="nav-item">
                                                    <a class="nav-link lang_link active"
                                                    href="#"
                                                    id="default-link">{{translate('messages.default')}}</a>
                                                </li>
                                                @foreach ($language as $lang)
                                                    <li class="nav-item">
                                                        <a class="nav-link lang_link"
                                                            href="#"
                                                            id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        <div class="lang_form col-md-6" id="default-form">
                                            <div class="form-group">
                                                <label class="input-label" for="default_title">{{translate('messages.title')}} ({{translate('messages.default')}})</label>
                                                <input type="text" name="title[]" id="default_title" class="form-control" placeholder="{{translate('messages.new_banner')}}" value="{{$banner?->getRawOriginal('title')}}">
                                            </div>
                                            <input type="hidden" name="lang[]" value="default">
                                        </div>
                                        @foreach($language as $lang)
                                            <?php
                                                if(count($banner['translations'])){
                                                    $translate = [];
                                                    foreach($banner['translations'] as $t)
                                                    {
                                                        if($t->locale == $lang && $t->key=="title"){
                                                            $translate[$lang]['title'] = $t->value;
                                                        }
                                                    }
                                                }
                                            ?>
                                            <div class="d-none lang_form col-md-6" id="{{$lang}}-form">
                                                <div class="form-group">
                                                    <label class="input-label" for="{{$lang}}_title">{{translate('messages.title')}} ({{strtoupper($lang)}})</label>
                                                    <input type="text" name="title[]" id="{{$lang}}_title" class="form-control" placeholder="{{translate('messages.new_banner')}}" value="{{$translate[$lang]['title']??''}}">
                                                </div>
                                                <input type="hidden" name="lang[]" value="{{$lang}}">
                                            </div>
                                        @endforeach
                                    @else
                                    <div id="default-form" class="col-md-6">
                                        <div class="form-group">
                                            <label class="input-label" for="exampleFormControlInput1">{{translate('messages.title')}} ({{ translate('messages.default') }})</label>
                                            <input type="text" name="title[]" class="form-control" placeholder="{{translate('messages.new_banner')}}" value="{{$banner['title']}}" maxlength="100">
                                        </div>
                                        <input type="hidden" name="lang[]" value="default">
                                    </div>
                                    @endif

                                    <div class="form-group col-md-6">
                                        <label class="input-label" for="title">City</label>
                                        <select name="zone_id" id="zone" class="form-control js-select2-custom">
                                            <option disabled selected>---{{translate('messages.select')}}---</option>
                                            @foreach($zones as $zone)
                                                @if(isset(auth('admin')->user()->zone_id))
                                                    @if(auth('admin')->user()->zone_id == $zone->id)
                                                        <option value="{{$zone['id']}}" {{$zone->id == $banner->zone_id?'selected':''}}>{{$zone['name']}}</option>
                                                    @endif
                                                @else
                                                <option value="{{$zone['id']}}" {{$zone->id == $banner->zone_id?'selected':''}}>{{$zone['name']}}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="input-label">Platform</label>
                                        <select name="platform" id="platform" class="form-control">
                                            <option value="all" {{ ($banner->platform ?? 'all') == 'all' ? 'selected' : '' }}>All</option>
                                            <option value="app" {{ $banner->platform == 'app' ? 'selected' : '' }}>App</option>
                                            <option value="web" {{ $banner->platform == 'web' ? 'selected' : '' }}>Web</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.banner_type')}}</label>
                                        <select name="banner_type" id="banner_type" class="form-control">
                                            <option value="store_wise" {{$banner->type == 'store_wise'? 'selected':'' }}>{{translate('messages.store_wise')}}</option>
                                            <option value="item_wise" {{$banner->type == 'item_wise'? 'selected':'' }}>{{translate('messages.item_wise')}}</option>
                                            <option value="module_wise" {{$banner->type == 'module_wise'? 'selected':'' }}>{{translate('messages.module_wise')}}</option>
                                            <option value="category_wise" {{$banner->type == 'category_wise'? 'selected':'' }}>{{translate('messages.category_wise')}}</option>
                                            <option value="default" {{$banner->type == 'default'? 'selected':'' }}>{{translate('messages.default')}}</option>
                                            <option value="self" {{$banner->type == 'self'? 'selected':'' }}>Self Banner</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6" id="store_wise">
                                        <label class="input-label" for="exampleFormControlSelect1">{{translate('messages.store')}}<span
                                                class="input-label-secondary"></span></label>
                                        <select name="store_id" id="store_id" class="js-data-example-ajax form-control" title="{{translate('messages.select_store')}}">
                                        @if($banner->type=='store_wise')
                                        @php($store = \App\Models\Store::where('id', $banner->data)->first())
                                            @if($store)
                                            <option value="{{$store->id}}" selected>{{$store->name}}</option>
                                            @endif
                                        @endif
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6" id="item_wise">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.select_item')}}</label>
                                        <select name="item_id" id="choice_item" class="form-control js-select2-custom" placeholder="{{translate('messages.select_item')}}">

                                        </select>
                                    </div>
                                    <div class="form-group col-md-6" id="module_wise">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.select_module')}}</label>
                                        <select name="module_id" class="form-control js-select2-custom" placeholder="{{translate('messages.select_module')}}">
                                            <option value="6" {{ $banner->data == 6 && $banner->type == 'module_wise' ? 'selected' : '' }}>MY CITY</option>
                                            <option value="5" {{ $banner->data == 5 && $banner->type == 'module_wise' ? 'selected' : '' }}>SHOPPING</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6 paid_field" style="display: none;" id="customer_wise">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.bill_to')}}</label>
                                        <select name="user_id" class="form-control js-select2-custom" placeholder="{{translate('messages.select_user')}}">
                                            <option value=""></option>
                                            @foreach($users as $u)
                                            <option value="{{$u->id}}">{{$u->phone . ' | ' . $u->f_name. ' ' . $u->l_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6 paid_field">
                                        <label class="input-label" for="exampleFormControlInput1">Price</label>
                                        <input name="price" type="number" class="form-control" placeholder="Price" value="{{ $banner->price ?? '' }}">
                                    </div>
                                    <div class="form-group col-md-6 paid_field">
                                        <label class="input-label" for="exampleFormControlInput1">Validity</label>
                                        <div class="row px-3">
                                            <input name="validity_count" type="number" class="form-control col-6" placeholder="Validity" value="{{ $banner->validity_count ?? '' }}">
                                            <select name="validity_type" class="form-control col-6">
                                                <option value="Days" {{ ($banner->validity_type ?? '') == 'Days' ? 'selected' : '' }}>Days</option>
                                                <option value="Months" {{ ($banner->validity_type ?? '') == 'Months' ? 'selected' : '' }}>Months</option>
                                                <option value="Years" {{ ($banner->validity_type ?? '') == 'Years' ? 'selected' : '' }}>Years</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6 paid_field">
                                        <label class="input-label" for="exampleFormControlInput1">GST</label>
                                        <div class="row px-3">
                                            <input name="gst_percent" type="text" class="form-control col-6" placeholder="GST %" value="{{ $banner->gst_percent ?? '' }}">
                                            <input name="hsn" type="text" class="form-control col-6" placeholder="HSN" value="{{ $banner->hsn ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6" id="category_wise">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.select_category')}}</label>
                                        <select name="category_id" class="form-control js-select2-custom" placeholder="{{translate('messages.select_category')}}">
                                            @foreach($categories as $cat)
                                            <option value="{{$cat->id}}" {{ $banner->data == $cat->id && $banner->type == 'category_wise' ? 'selected' : '' }}>{{$cat->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.default_link')}} ({{ translate('messages.optional') }})</label>
                                        <input type="text" name="default_link" class="form-control" value="{{ $banner->default_link }}" placeholder="{{translate('messages.default_link')}}">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="h-100 d-flex flex-column">
                                        <label class="mb-0 d-block text-center">
                                            {{translate('messages.banner_image')}}
                                            <small class="text-danger">* ( {{translate('messages.ratio')}} <span id="ratio_hint">{{ $banner->platform == 'app' ? '12:5' : '3:1' }}</span> )</small>
                                        </label>
                                        <div class="text-center py-3">
                                            <img class="img--vertical onerror-image" id="viewer" data-onerror-image="{{asset('public/assets/admin/img/900x400/img1.jpg')}}" src="{{\App\CentralLogics\Helpers::onerror_image_helper($banner['image'], asset('storage/app/public/banner/').'/'.$banner['image'], asset('public/assets/admin/img/900x400/img1.jpg'), 'banner/') }}"
                                            alt="banner image"/>
                                        </div>
                                        <div class="custom-file">
                                            <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                                accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                            <label class="custom-file-label" for="customFileEg1">{{translate('messages.choose_file')}}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-4">
                                    <div class="btn--container justify-content-end">
                                        <button type="reset" id="reset_btn" class="btn btn--reset">{{translate('messages.reset')}}</button>
                                        <button type="submit" class="btn btn--primary">{{translate('messages.update')}}</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- End Table -->
        </div>
    </div>

@endsection

@push('script_2')
    <script src="{{asset('public/assets/admin')}}/js/view-pages/banner-edit.js"></script>
    <script>
        "use strict";

        $("#platform").on('change', function(){
            if($(this).val() == 'app'){
                $("#ratio_hint").text('12:5');
            } else {
                $("#ratio_hint").text('3:1');
            }
        });

        var zone_id = {{$banner->zone_id}};

        var module_id = {{$banner->module_id}};

         function get_items()
        {
            @if (Str::contains(request()->getHost(), 'staging.mychitti.net'))
            var nurl = '{{url('/')}}/admin/item/get-items?module_id='+module_id;
            @else
            var nurl = '{{url('/')}}/item/get-items?module_id='+module_id;
            @endif

            if(!Array.isArray(zone_id))
            {
                nurl += '&zone_id='+zone_id;
            }

            $.get({
                url: nurl,
                dataType: 'json',
                success: function (data) {
                    $('#choice_item').empty().append(data.options);
                }
            });
        }
        $(document).on('ready', function () {
            banner_type_change('{{$banner->type}}');
            get_items();

            $('#zone').on('change', function(){
                if($(this).val())
                {
                    zone_id = $(this).val();
                    get_items();
                }
                else
                {
                    zone_id = true;
                }
            });

            $('.js-data-example-ajax').select2({
                minimumInputLength: 2,
                ajax: {
                    url: '{{url('/')}}/store/get-stores',
                    data: function (params) {
                        return {
                            q: params.term, // search term
                            zone_ids: [zone_id],
                            page: params.page,
                            module_id: module_id
                        };
                    },
                    processResults: function (data) {
                        return {
                        results: data
                        };
                    },
                    __port: function (params, success, failure) {
                        var $request = $.ajax(params);

                        $request.then(success);
                        $request.fail(failure);

                        return $request;
                    }
                }
            });



            $('.js-select2-custom').each(function () {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });


        @if($banner->type == 'item_wise')
        getRequest('{{url('/')}}/item/get-items?module_id={{$banner->module_id}}&zone_id={{$banner->zone_id}}&data[]={{$banner->data}}','choice_item');
        @endif
        $('#banner_form').on('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{route('admin.banner.update', [$banner['id']])}}",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    if (data.errors) {
                        for (var i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    } else {
                        toastr.success("{{translate('messages.banner_updated_successfully')}}", {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function () {
                            // location.href = "{{url()->full()}}";
                        }, 2000);
                    }
                }
            });
        });
    </script>
@endpush
