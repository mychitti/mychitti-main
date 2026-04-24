@extends('front-views.layout')

@section('title', 'Store Reviews')

@section('seo')
@endsection

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
    

        .rating-stars {
            position: relative;
            display: inline-block;
            font-size: 15px;
            color: #ccc;
        }

        .stars-base i {
            color: #ccc;
        }

        .stars-fill {
            position: absolute;
            top: 0;
            left: 0;
            overflow: hidden;
            white-space: nowrap;
            color: #f7a103;
            width: 0;
        }
    .store_logo {
            height: 75px;
            {{-- aspect-ratio: 1; --}} padding: 15px 5px;
            {{-- object-fit: cover; --}}
        }

        .fruite .fruite-item {
            margin: 10px 0;
        }

        .st-btn {
            margin: 3px !important;
        }
</style>
@endpush

@section('content')
    <div class="spacer" style="height: 50px;"></div>

    <!-- Single Page Header start -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Reviews</h1>
        <ol class="breadcrumb justify-content-center mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active text-white">Reviews</li>
        </ol>
    </div>
    <!-- Single Page Header End -->


    <!-- Contact Start -->
    <div class="container-fluid contact py-5">
        <div class="container py-5">
            @if (count($reviews))
                <div class="" id="store-ratings" aria-labelledby="pills-store-ratings-tab">
                    <div class="section_spacing">
                        {{-- <h3 class="sec_heading">Store Ratings</h3> --}}

                            <div class="col-lg-7" id="nav-mission">
                                <h5 class="">Reviews - {{ ucwords($store->name) }} </h5>
                                <br>
                                @foreach ($reviews as $rev)
                                    <div class="d-flex border rounded my-2  p-2">
                                        <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                            class="img-fluid rounded m-2 r_profile_img" style=""
                                            alt="{{ $rev->f_name . ' ' . $rev->l_name }}">
                                        <div class="d-flex flex-column w-100">
                                            <div class="d-flex justify-content-between review_info">
                                                <div class="">
                                                    <p class="mb-2 date_time" style="">
                                                        {{ _formatted_datetime($rev->created_at) }}</p>
                                                    <div class="d-flex ">
                                                        <h5 class="r_name">{{ $rev->f_name . ' ' . $rev->l_name }}
                                                        </h5>
                                                        <div class="d-flex ">
                                                            @for ($i = 1; $i < 6; $i++)
                                                                <i
                                                                    class="rating_star fa fa-star {{ $rev->rating >= $i ? 'text-secondary' : '' }}"></i>
                                                            @endfor
                                                        </div>
                                                    </div>
                                                    <p class="text-dark">{{ $rev->comment }}</p>
                                                </div>

                                                @if ($rev->attachment)
                                                    @php $attachments = (array) $rev->attachment; @endphp
                                                    @if (!empty($attachments))
                                                        <div class="d-flex">
                                                            @foreach ($attachments as $img)
                                                                <a target="_blank" class="mx-1"
                                                                    href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"><img
                                                                        class="rounded" style="width: 55px;"
                                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                                        alt="review"></a>
                                                                <a target="_blank"
                                                                    href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"><img
                                                                        class="rounded" style="width: 55px;"
                                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                                        alt="review"></a>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                            @if ($rev->reply)
                                                <div class="d-flex border rounded  p-2">
                                                    <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->logo, asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                                        class="img-fluid rounded m-2 reply_img" style=""
                                                        alt="{{ $store->name }}">
                                                    <div class="">
                                                        <p class="mb-0 date_time" style="">
                                                            {{ _formatted_datetime($rev->replied_at) }}</p>

                                                        <p class="text-dark">{{ $rev->reply }}</p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>


                                    </div>
                                @endforeach
                                @if (!count($reviews))
                                    No Reviews yet...
                                
                                @endif

                            </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script_2')
@endpush
