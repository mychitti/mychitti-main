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
 
<!-- Fruits Shop Start-->
<div class="container-fluid fruite py-5">
    <div class="container py-5">
        <div class="row g-4">  
            <div class="col-lg-12">
              
                <div class="row g-4">
                  
                    <div class="col-lg-9">
                        <div class="row g-4 justify-content-center">

                                <div class="rounded position-relative ">
                                    <i>{{$blog->cat_name}}</i>  <br>
                                    <i>Posted at : {{_formatted_date($blog->created_at)}}</i> 
                                    <h1 class="mt-3">{{$blog->title}}</h1>  
                                    <div class="" style="">
                                        <img style="object-fit:cover" data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}" src="{{\App\CentralLogics\Helpers::onerror_image_helper($blog->image, asset('storage/app/public/blog/').'/'.$blog->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'blog/') }}" class="img-fluid w-100 " alt="post">
                                    </div>
                                       
                                    <div class="mt-3">{!! $blog->description !!}</div>
                                        
                                     
                                </div>
                        
                   
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
                                                <a href="{{route('blog.category', [$ct->slug])}}"><img class="rounded border" style="height:40px; width:40px; object-fit:cover;" data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}" src="{{\App\CentralLogics\Helpers::onerror_image_helper($ct->image, asset('storage/app/public/blog/category/').'/'.$ct->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'blog/category/') }}"> &nbsp; &nbsp;{{$ct->name}}</a>                                        
                                            </div>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="mb-3">
                                    <h4>Explore More Blogs</h4>
                                    <ul class="list-unstyled fruite-categorie">
                                        @foreach($all_blogs as $ct)
                                        <li class="border rounded my-2 p-2">  
                                            <div class="d-flex justify-content-between fruite-name">
                                                <a href="{{$ct->slug}}" class="d-flex text-dark"><img class="rounded border" style="height:80px; width:80px; object-fit:cover;" data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}" src="{{\App\CentralLogics\Helpers::onerror_image_helper($ct->image, asset('storage/app/public/blog/').'/'.$ct->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'blog/') }}">
                                               <p style="margin-left: 15px;line-height: 25px;    margin-bottom: 0px;">{{substr($ct->title, 0, 60)}}...</p> 
                                            </a>
                                               
                                            </div>
                                        </li> 
                                        @endforeach
                                        <li>
                                            <div class="d-flex justify-content-between fruite-name">
                                                <a href="{{$type == 'mc_vendor' ? route('blog-mc-vendor-hub') : route('blog')}}" ><b>+ View All</b></a>
                                            </div>
                                        </li>
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