@foreach($delivery_men as $key=>$dm)
<tr>
    <td>{{$key+1}}</td>
        <td>
            <a class="table-rest-info" href="{{route('admin.users.delivery-man.preview',[$dm['id']])}}">
                <img class="onerror-image" data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"
                src="{{\App\CentralLogics\Helpers::onerror_image_helper($dm['image'], asset('storage/app/public/delivery-man/').'/'.$dm['image'], asset('public/assets/admin/img/160x160/img1.jpg'), 'delivery-man/') }}"
                alt="{{$dm['f_name']}} {{$dm['l_name']}}">
                <div class="info">
                    <h5 class="text-hover-primary mb-0">{{$dm['f_name'].' '.$dm['l_name']}}</h5>
                    <span class="d-block text-body">
                        <span class="rating">
                        <i class="tio-star"></i> {{count($dm->rating)>0?number_format($dm->rating[0]->average, 1, '.', ' '):0}}
                        </span>
                    </span>
                </div>
            </a>
        </td>
    <td>
            <a href="javascript:;" style="cursor:default;"
                class="textToCopy">{{ $dm['phone'] }}</a>
            <button
                class="copy-btn bg-transparent outline-none border-0">
                <i class="tio-copy"></i>
            </button>
    </td>
    <td>
        @if($dm->zone)
        <label class="text--title font-medium mb-0">{{$dm->zone->name}}</label>
        @else
        <label class="text--title font-medium mb-0">{{translate('messages.zone_deleted')}}</label>
        @endif
    </td>
    <td>
        <a class="deco-none">{{count($dm['orders'])}}</a>
    </td>
    <td>
        <div>
            {{translate('messages.currently_assigned_orders')}} : {{$dm->current_orders}}
        </div>
        <div>
            {{translate('messages.active_status')}} :
            @if($dm->application_status == 'approved')
                @if($dm->active)
                <strong class="text-capitalize text-primary">{{translate('messages.online')}}</strong>
                @else
                <strong class="text-capitalize text-secondary">{{translate('messages.offline')}}</strong>
                @endif
            @elseif ($dm->application_status == 'denied')
                <strong class="text-capitalize text-danger">{{translate('messages.denied')}}</strong>
            @else
                <strong class="text-capitalize text-info">{{translate('messages.pending')}}</strong>
            @endif
        </div>
    </td>
    <td>
        <div class="btn--container justify-content-center">
            <a class="btn action-btn btn--primary btn-outline-primary" href="{{route('admin.users.delivery-man.edit',[$dm['id']])}}" title="{{translate('messages.edit')}}"><i class="tio-edit"></i>
                </a>
            <a class="btn action-btn btn--danger btn-outline-danger form-alert" href="javascript:" data-id="delivery-man-{{$dm['id']}}" data-message="{{ translate('Want to remove this deliveryman ?') }}" title="{{translate('messages.delete')}}"><i class="tio-delete-outlined"></i>
            </a>
            <form action="{{route('admin.users.delivery-man.delete',[$dm['id']])}}" method="post" id="delivery-man-{{$dm['id']}}">
                @csrf @method('delete')
            </form>
        </div>
    </td>
</tr>
@endforeach
<script src="{{asset('public/assets/admin')}}/js/view-pages/common.js"></script>
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