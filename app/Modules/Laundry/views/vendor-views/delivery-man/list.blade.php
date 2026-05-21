@extends('layouts.vendor.app')

@section('title',translate('messages.deliverymen'))


@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/deliveryman.png')}}" class="w--30" alt="">
                </span>
                <span>
                   {{translate('messages.deliveryman')}}<span class="badge badge-soft-dark ml-2" id="itemCount">{{$delivery_men->total()}}</span>
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header justify-content-end">
                <form class="search-form" >
                    <div class="input-group input--group">
                        <input  type="search" name="search" class="form-control" value="{{request()?->search ?? ''}}"
                                placeholder="{{translate('messages.ex_search_name')}}" aria-label="{{translate('messages.ex_search_name')}}" >
                        <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                    </div>
                    <!-- End Search -->
                </form>
            </div>
            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive datatable-custom">
                <table id="columnSearchDatatable"
                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                        data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false
                        }'>
                    <thead class="thead-light">
                    <tr>
                        <th class="border-0 text-capitalize">{{translate('messages.#')}}</th>
                        <th class="border-0 text-capitalize">{{translate('messages.name')}}</th>
                        <th class="border-0 text-capitalize">{{translate('messages.availability_status')}}</th>
                        <th class="border-0 text-capitalize">{{translate('messages.phone')}}</th>
                        <th class="border-0 text-capitalize text-center">{{translate('messages.active_orders')}}</th>
                        <th class="border-0 text-capitalize text-center">{{translate('messages.action')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach($delivery_men as $key=>$dm)
                        <tr>
                            <td>{{$key+$delivery_men->firstItem()}}</td>
                            <td>
                                <a class="media align-items-center" href="{{route('vendor.delivery-man.preview',[$dm['id']])}}">
                                    <img class="avatar avatar-lg mr-3 onerror-image"
                                         data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"
                                         src="{{\App\CentralLogics\Helpers::onerror_image_helper($dm['image'], asset('storage/app/public/delivery-man').'/'.$dm['image'], asset('public/assets/admin/img/160x160/img1.jpg'), 'delivery-man/') }}"
                                         alt="{{$dm['f_name']}} {{$dm['l_name']}}">
                                    <div class="media-body">
                                        <h5 class="text-hover-primary mb-0">{{$dm['f_name'].' '.$dm['l_name']}}</h5>
                                        <span class="rating">
                                            <i class="tio-star"></i> {{count($dm->rating)>0?number_format($dm->rating[0]->average, 1, '.', ' '):0}}
                                        </span>
                                    </div>
                                </a>
                            </td>
                            <td>
                                <div>
                                    {{translate('messages.currently_assigned_orders')}} : {{$dm->current_orders}}
                                </div>
                                <div>
                                    {{translate('messages.active_status')}} :
                                    @if($dm->application_status == 'approved')
                                        @if($dm->active)
                                        <strong class="text-capitalize text-success">{{translate('messages.online')}}</strong>
                                        @else
                                        <strong class="text-capitalize text-danger">{{translate('messages.offline')}}</strong>
                                        @endif
                                    @elseif ($dm->application_status == 'denied')
                                        <strong class="text-capitalize text-danger">{{translate('messages.denied')}}</strong>
                                    @else
                                        <strong class="text-capitalize text-primary">{{translate('messages.pending')}}</strong>
                                    @endif
                                </div>
                            </td>
                            <td>

                               <a href="javascript:;" style="cursor:default;"
                                                                    class="textToCopy">{{ $dm['phone'] }}</a>
                                                                <button
                                                                    class="copy-btn bg-transparent outline-none border-0">
                                                                    <i class="tio-copy"></i>
                                                                </button>
                                                                
                            </td>
                            <td class="text-center">
                                {{ $dm->orders ? count($dm->orders):0 }}
                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a class="btn action-btn btn--primary btn-outline-primary" href="{{route('vendor.delivery-man.edit',[$dm['id']])}}" title="{{translate('messages.edit')}}"><i class="tio-edit"></i>
                                    </a>
                                    <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                       data-id="delivery-man-{{$dm['id']}}"
                                       data-message="{{translate('Want_to_remove_this_deliveryman_?')}}"
                                       href="javascript:"  title="{{translate('messages.delete')}}"><i class="tio-delete-outlined"></i>
                                    </a>
                                </div>
                                <form action="{{route('vendor.delivery-man.delete',[$dm['id']])}}" method="post" id="delivery-man-{{$dm['id']}}">
                                    @csrf @method('delete')
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @if(count($delivery_men) !== 0)
                <hr>
                @endif
                <div class="page-area">
                    <table>
                        <tfoot>
                        {!! $delivery_men->links() !!}
                        </tfoot>
                    </table>
                </div>
                    @if(count($delivery_men) === 0)
                    <div class="empty--data">
                        <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                        <h5>
                            {{translate('no_data_found')}}
                        </h5>
                    </div>
                    @endif

            </div>
            <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    <script src="{{asset('public/assets/admin/js/view-pages/datatable-search.js')}}"></script>
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
@endpush
