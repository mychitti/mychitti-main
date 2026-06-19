@extends('layouts.vendor.app')
@section('title',translate('messages.store_view'))
@push('css_or_js')
    <!-- Custom styles for this page -->
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between">
            <h2 class="page-header-title text-capitalize my-2">
                <img class="w--26" src="{{asset('/public/assets/admin/img/store.png')}}" alt="public">
                <span>
                    {{translate('messages.my_store_info')}}
                </span>
            </h2>
            <div class="my-2">
                <a class="btn btn--primary" href="{{route('vendor.shop.edit')}}"><i class="tio-edit"></i>{{translate('messages.edit_store_information')}}</a>
            </div>
        </div>
    </div>
    <div class="card border-0">
        <div class="card-body p-0">
            @if($shop->cover_photo)
            <div>
                <img class="my-restaurant-img onerror-image" src="{{\App\CentralLogics\Helpers::onerror_image_helper($shop->cover_photo, asset('storage/app/public/store/cover/').'/'.$shop->cover_photo, asset('public/assets/admin/img/900x400/img1.jpg'), 'store/cover/') }}"
                data-onerror-image="{{asset('public/assets/admin/img/900x400/img1.jpg')}}">
            </div>
            @endif
            <div class="my-resturant--card">

                @if($shop->image=='def.png')
                <div class="my-resturant--avatar">
                    <img class="border onerror-image"
                    src="{{asset('public/assets/back-end')}}/img/shop.png"
                    data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}" alt="User Pic">
                </div>
                @else
                    <div class="my-resturant--avatar onerror-image">
                        <img src="{{\App\CentralLogics\Helpers::onerror_image_helper($shop->logo, asset('storage/app/public/store/').'/'.$shop->logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                        class="border" data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}" alt="">
                    </div>
                @endif

                <div class="my-resturant--content">
                    <span class="d-block mb-1 pb-1">
                        <strong> {{translate('messages.name')}} :</strong>{{$shop->name}}
                    </span>
                    <span class="d-block mb-1 pb-1">
                        <strong>{{translate('messages.phone')}} :</strong>

                            <a href="javascript:;" style="cursor:default;"
                                                                    class="textToCopy">{{ $shop->phone }}</a>
                                                                <button
                                                                    class="copy-btn bg-transparent outline-none border-0">
                                                                    <i class="tio-copy"></i>
                                                                </button>

                    </span>
                    <span class="d-block mb-1 pb-1">
                        <strong>{{translate('messages.address')}} : </strong> {{$shop->address}}
                    </span>
                    <!-- <span class="d-block mb-1 pb-1">
                        <strong>{{translate('messages.admin_commission')}} : </strong> {{(isset($shop->comission)? $shop->comission:\App\Models\BusinessSetting::where('key','admin_commission')->first()->value)}}%
                    </span> -->
                    <span class="d-block mb-1 pb-1">
                        <strong>{{translate('messages.vat/tax')}} : </strong> {{$shop->tax}}%</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="card border-0 mt-2">
        <div class="card-header">
            <h5 class="card-title toggle-switch toggle-switch-sm d-flex justify-content-between">
                <span class="card-header-icon mr-1"><i class="tio-dashboard"></i></span>
                <span>{{translate('Announcement')}}</span><span class="input-label-secondary" data-toggle="tooltip" data-placement="right" data-original-title="{{translate('This_feature_is_for_sharing_important_information_or_announcements_related_to_the_store.')}}"><img src="{{asset('/public/assets/admin/img/info-circle.svg')}}" alt="{{translate('messages.This_feature_is_for_sharing_important_information_or_announcements_related_to_the_store')}}"></span>
            </h5>
            <label class="toggle-switch toggle-switch-sm" for="announcement_status">
                <input class="toggle-switch-input dynamic-checkbox" type="checkbox" id="announcement_status"
                       data-id="announcement_status"
                       data-type="status"
                       data-image-on='{{asset('/public/assets/admin/img/modal')}}/digital-payment-on.png'
                       data-image-off="{{asset('/public/assets/admin/img/modal')}}/digital-payment-off.png"
                       data-title-on="{{translate('Do_you_want_to_enable_the_announcement')}}"
                       data-title-off="{{translate('Do_you_want_to_disable_the_announcement')}}"
                       data-text-on="<p>{{translate('User_will_able_to_see_the_Announcement_on_the_store_page.')}}</p>"
                       data-text-off="<p>{{translate('User_will_not_be_able_to_see_the_Announcement_on_the_store_page')}}</p>"
                       name="announcement" value="1" {{$shop->announcement?'checked':''}}>
                                <span class="toggle-switch-label">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
            </label>


        </div>
        <form action="{{route('vendor.business-settings.toggle-settings',[$shop->id,$shop->announcement?0:1, 'announcement'])}}"
            method="get" id="announcement_status_form">
            </form>
        <div class="card-body">
            <form action="{{route('vendor.shop.update-message')}}" method="post">
            @csrf
                <textarea name="announcement_message" id="" class="form-control" rows="5" placeholder="{{ translate('messages.ex_:_ABC_Company') }}">{{ $shop->announcement_message??'' }}</textarea>
                <div class="justify-content-end btn--container mt-2">
                    <button type="submit" class="btn btn--primary">{{translate('publish')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
   $(document).ready(function () {
    $(".copy-btn").on("click", function () {
        // Get the previous <p> or span element text
        var text = $(this).prev(".textToCopy").text().trim();
        console.log(text); // Debugging

        if (navigator.clipboard && window.isSecureContext) {
            // Modern way to copy
            navigator.clipboard.writeText(text).then(() => { 
                console.log("Copied successfully!");
            }).catch(err => {
                console.error("Clipboard copy failed", err);
            });
        } else {
            // Fallback for older browsers
            var tempInput = $("<textarea>"); // Use textarea instead of input
            $("body").append(tempInput);
            tempInput.val(text).css({
                position: "absolute",
                left: "-9999px", // Hide offscreen
            }).select();
            document.execCommand("copy");
            tempInput.remove();
        }
        $(this).html("Copied!");
        setTimeout(() => $(this).html('<i class="tio-copy"></i>'), 1000);
    });
});
</script>
@endsection
