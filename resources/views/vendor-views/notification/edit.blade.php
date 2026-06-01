@extends('layouts.vendor.app')

@section('title',translate('messages.update_ad'))

@push('css_or_js')
<style>
    .preview-thumb-wrap {
        position: relative;
        width: 80px;
        height: 100px;
        border-radius: 6px; 
        overflow: hidden;
        border: 1px solid #ddd; 
        background: #fff;
    }
    .preview-thumb-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .preview-thumb-remove {
        position: absolute;
        top: 2px;
        right: 2px;
        background: rgba(220, 53, 69, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }
    .preview-thumb-remove:hover {
        background: rgb(220, 53, 69);
    }
</style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/notification.png')}}" class="w--26" alt="">
                </span>
                <span>
                    {{translate('messages.ad_update')}}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="card">
            <div class="card-body">
                <form action="{{route('vendor.notification.update',[$notification['id']])}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.title')}}</label>
                                        <input type="text" value="{{$notification['title']}}" name="notification_title" class="form-control" placeholder="{{translate('messages.new_ad')}}" required maxlength="191">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.zone')}}</label>
                                        <select name="zone" id="zone" class="form-control js-select2-custom" >
                                            <option value="all" {{isset($notification->zone_id)?'':'selected'}}>{{translate('messages.all_zone')}}</option>
                                            @foreach($zones as $zone)
                                                <option value="{{$zone['id']}}"  {{$notification->zone_id==$zone['id']?'selected':''}}>{{$zone['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="tergat">{{translate('messages.send_to')}}</label>
                                        <select name="tergat" class="form-control" id="tergat" data-placeholder="{{translate('messages.select_tergat')}}" required>
                                            <option value="customer" {{$notification->tergat=='customer'?'selected':''}}>{{translate('messages.customer')}}</option>
                                            <option value="deliveryman" {{$notification->tergat=='deliveryman'?'selected':''}}>{{translate('messages.deliveryman')}}</option>
                                            <option value="store" {{$notification->tergat=='store'?'selected':''}}>{{translate('messages.store')}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.description')}}</label>
                                        <textarea name="description" class="form-control" maxlength="1000" required>{{$notification['description']}}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="h-100 d-flex flex-column">
                                <label class="d-block text-center mt-auto mb-0 font-weight-bold">
                                    {{translate('messages.image')}}
                                    <small class="text-danger">* (Ratio 1080x1350, you can select multiple)</small>
                                </label>
                                
                                <!-- Existing Images Grid -->
                                <div class="d-flex flex-wrap justify-content-center py-2" id="existing_images_wrap">
                                    @php
                                        $images = [];
                                        if ($notification->images) {
                                            $images = is_array($notification->images) ? $notification->images : json_decode($notification->images, true);
                                        } elseif ($notification->image) {
                                            $images = [$notification->image];
                                        }
                                    @endphp
                                    @foreach($images as $img)
                                        <div class="preview-thumb-wrap m-1" data-image="{{ $img }}">
                                            <img src="{{ asset('storage/app/public/notification') . '/' . $img }}"
                                                 onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'">
                                            <button type="button" class="preview-thumb-remove remove-existing-img" data-image="{{ $img }}">&times;</button>
                                        </div>
                                    @endforeach
                                </div>
                                <div id="removed_images_container"></div>

                                <!-- New Images Previews Grid -->
                                <div id="new_image_previews_container" class="d-flex flex-wrap justify-content-center py-2" style="min-height: 50px;">
                                </div>

                                <div class="custom-file">
                                    <input type="file" name="images[]" id="adImagesInput" class="custom-file-input"
                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*"
                                        multiple>
                                    <label class="custom-file-label" for="adImagesInput">{{translate('messages.choose_files')}}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Schedule toggle -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex align-items-center mb-2">
                                <div class="custom-control custom-switch mr-3">
                                    <input type="checkbox" class="custom-control-input" id="vendor_schedule_toggle" name="is_scheduled" value="1" {{ $notification->is_scheduled ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="vendor_schedule_toggle">Schedule for later</label>
                                </div>
                            </div>
                            <div id="vendor_schedule_wrap" class="mb-3" style="{{ $notification->is_scheduled ? 'display:block;' : 'display:none;' }}">
                                <label class="input-label">Schedule Date &amp; Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" id="vendor_scheduled_at" name="scheduled_at" class="form-control"
                                    min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}"
                                    value="{{ $notification->scheduled_at ? \Carbon\Carbon::parse($notification->scheduled_at)->format('Y-m-d\TH:i') : '' }}">
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

@include('vendor-views.partials.image-cropper-modal')

@endsection

@push('script_2')
    <script>
        "use strict";

        // Remove existing image
        $('.remove-existing-img').on('click', function() {
            var imgName = $(this).data('image');
            $('#removed_images_container').append('<input type="hidden" name="removed_images[]" value="' + imgName + '">');
            $(this).parent('.preview-thumb-wrap').remove();
        });

        var selectedAdFiles = [];

        $('#adImagesInput').on('change', function(e) {
            var files = e.target.files;
            for (var i = 0; i < files.length; i++) {
                selectedAdFiles.push(files[i]);
            }
            renderPreviews();
        });

        function renderPreviews() {
            var container = $('#new_image_previews_container');
            container.empty();
            
            selectedAdFiles.forEach(function(file, index) {
                var reader = new FileReader();
                var wrap = $('<div class="preview-thumb-wrap m-1"></div>');
                var img = $('<img src="" alt="preview">');
                var btn = $('<button type="button" class="preview-thumb-remove">&times;</button>');
                
                btn.on('click', function() {
                    selectedAdFiles.splice(index, 1);
                    renderPreviews();
                });

                wrap.append(img).append(btn);
                container.append(wrap);

                reader.onload = function(e) {
                    img.attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            });

            updateFileInput();
        }

        function updateFileInput() {
            var dt = new DataTransfer();
            selectedAdFiles.forEach(function(file) {
                dt.items.add(file);
            });
            document.getElementById('adImagesInput').files = dt.files;
            
            var labelText = selectedAdFiles.length > 0 
                ? selectedAdFiles.length + ' files selected' 
                : '{{ translate('messages.choose_files') }}';
            $('#adImagesInput').siblings('.custom-file-label').html(labelText);
        }

        // Schedule toggle
        $('#vendor_schedule_toggle').on('change', function() {
            if ($(this).is(':checked')) {
                $('#vendor_schedule_wrap').slideDown(200);
            } else {
                $('#vendor_schedule_wrap').slideUp(200);
            }
        });

        $('#reset_btn').click(function(){
            $('#zone').val("{{$notification->zone_id}}").trigger('change');
            location.reload();
        });
    </script>
@endpush
