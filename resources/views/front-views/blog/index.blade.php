@extends('front-views.layout')

@section('title', 'Blog')

@section('seo')
    <meta content="3" name="keywords">
    <meta content="32" name="description">
@endsection

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <!-- Single Page Header start -->
    <!-- <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Blog</h1>
        <ol class="breadcrumb justify-content-center mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active text-white">Blog</li>
        </ol>
    </div> -->
    <!-- Single Page Header End -->


    <!-- Fruits Shop Start-->
    <div class="container-fluid fruite py-5">


        <div class="container py-5">
            <h1 style="font-size: 30px;">Explore Our Blog Posts</h1>
            <div class="row g-4">
                <div class="col-lg-12">

                    <div class="row g-4">

                        <div class="col-lg-9">
                            <div class="row g-4 ">

                                @foreach ($blogs as $b)
                                    <div class="col-md-9 col-lg-6 col-xl-6">
                                        <a href="{{ route('blog.post', [$b->slug]) }}">
                                            <div class=" position-relative ">
                                                <div class=""
                                                    style="height: 290px !important;  border: 1px solid #ffb524;  border-bottom: 0px;">
                                                    <img style="height: 290px !important;object-fit:cover"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($b->image, asset('storage/app/public/blog/') . '/' . $b->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'blog/') }}"
                                                        class="img-fluid w-100 " alt="{{ substr($b->title, 0, 80) }}">
                                                </div>
                                                <!-- <div class="text-white bg-secondary px-3 py-1 rounded position-absolute" style="top: 10px; left: 10px; font-size: 12px;">{{ $b->cat_name }}</div> -->
                                                <div class="p-3 border border-secondary border-top-0 rounded-bottom">
                                                    <h4 style="   min-height: 51px;  ">{{ substr($b->title, 0, 80) }}...</h4>
                                                    <div>
                                                        <p>{{ substr($b->short_description, 0, 160) }}...</p>
                                                    </div>
                                                    <div class="d-flex justify-content-between flex-lg-wrap ">
                                                        <button class="btn btn-primary ">Read More</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                @endforeach

                                @if (!count($blogs))
                                    <img style="width: 400px;"
                                        src="{{ asset('public/assets/front/img/sorry-item-not-found-3328225-2809510.webp') }}"
                                        alt="no-results">
                                    <h4 style="text-align: center;">Oops! No blogs found</h4>
                                @endif

                                <div class='d-flex'>
                                    {{ $blogs->links() }}
                                </div>

                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="row g-4">
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <h4>Categories</h4>
                                        <ul class="list-unstyled fruite-categorie">
                                            @foreach ($all_categories as $ct)
                                                <li>
                                                    <div class="d-flex justify-content-between fruite-name">
                                                        <a href="{{ route('blog.category', [$ct->slug]) }}"><img
                                                                class="rounded border"
                                                                style="height:40px; width:40px; object-fit:cover;"
                                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($ct->image, asset('storage/app/public/blog/category/') . '/' . $ct->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'blog/category/') }}">
                                                            &nbsp; &nbsp;{{ $ct->name }}</a>

                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Fruits Shop End-->


@endsection

@push('script_2')
@endpush
