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
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Blog</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
        <li class="breadcrumb-item active text-white">Blog</li>
    </ol>
</div>
<!-- Single Page Header End -->


<!-- Fruits Shop Start-->
<div class="container-fluid fruite py-5">
    <div class="container py-5">
        <div class="row g-4">  
            <div class="col-lg-12">
              
                <div class="row g-4">
                  
                    <div class="col-lg-9">
                        <div class="row g-4 justify-content-center">

                         

                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="row g-4">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <h4>Categories</h4>
                                    <ul class="list-unstyled fruite-categorie">
                                        @foreach($all_categories as $ct)
                                        <li>
                                            <div class="d-flex justify-content-between fruite-name">
                                                <a href="{{$ct->slug}}"><img class="rounded border" style="height:40px; width:40px; object-fit:cover;" data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}" src="{{\App\CentralLogics\Helpers::onerror_image_helper($ct->image, asset('storage/app/public/blog/category/').'/'.$ct->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'blog/category/') }}"> &nbsp; &nbsp;{{$ct->name}}</a>
                                               
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