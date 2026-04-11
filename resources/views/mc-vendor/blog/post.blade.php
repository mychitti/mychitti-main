<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $blog->title }} — MCVendorHub Blog</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="{{ asset('storage/vendor_login/mc_vendor_hub_logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .my-nav-link {
            position: relative; color: #000; text-decoration: none;
            padding-bottom: 5px; font-weight: 500; transition: color 0.3s ease;
        }
        .my-nav-link::after {
            content: ""; position: absolute; bottom: 0; left: 0;
            height: 2px; width: 100%; background-color: #81c408;
            transform: scaleX(0); transform-origin: left; transition: transform 0.3s ease;
        }
        .my-nav-link:hover::after { transform: scaleX(1); }
        .blog-content img { max-width: 100%; height: auto; border-radius: 8px; }
        .blog-content p { line-height: 1.8; color: #444; }
        .blog-content h1, .blog-content h2, .blog-content h3 { margin-top: 1.5rem; }
        .category-sidebar a { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f0f0f0; color: #333; text-decoration: none; font-weight: 500; transition: color .2s; }
        .category-sidebar a:hover { color: #81c408; }
        .category-sidebar img { height: 40px; width: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; }
        .related-card { border: 1px solid #eee; border-radius: 8px; overflow: hidden; transition: box-shadow .2s; }
        .related-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .related-card img { height: 70px; width: 70px; object-fit: cover; flex-shrink: 0; }
        .page-hero { background: linear-gradient(135deg, #f0f7e6 0%, #e8f5d0 100%); padding: 36px 0 24px; border-bottom: 1px solid #d4edac; }
    </style>
</head>
<body>
    @include('mc-vendor.partials.nav')

    <div class="page-hero">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('vendor.mc-vendor.home') }}" class="text-decoration-none" style="color:#81c408;">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vendor.mc-vendor.blog-mc-vendor-hub') }}" class="text-decoration-none" style="color:#81c408;">Blog</a></li>
                    <li class="breadcrumb-item active">{{ substr($blog->title, 0, 50) }}{{ strlen($blog->title) > 50 ? '...' : '' }}</li>
                </ol>
            </nav>
            <span class="badge rounded-pill px-3 py-1" style="background:#81c408; color:#fff;">{{ $blog->cat_name }}</span>
        </div>
    </div>

    <div class="container py-5">
        <div class="row g-5">

            {{-- Main Content --}}
            <div class="col-lg-9">
                <h1 class="fw-bold mb-2">{{ $blog->title }}</h1>
                <p class="text-muted small mb-4">
                    <i class="fa-regular fa-calendar me-1"></i>
                    {{ \Carbon\Carbon::parse($blog->created_at)->format('d M Y') }}
                    &nbsp;&bull;&nbsp;
                    <i class="fa-regular fa-folder me-1"></i>
                    <a href="{{ route('vendor.mc-vendor.blog-mc-vendor-hub.category', $blog->cat_slug) }}"
                        class="text-decoration-none" style="color:#81c408;">{{ $blog->cat_name }}</a>
                </p>

                <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($blog->image, asset('storage/app/public/blog/') . '/' . $blog->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'blog/') }}"
                    alt="{{ $blog->title }}"
                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                    class="img-fluid rounded mb-4 w-100" style="max-height:480px; object-fit:cover;">

                <div class="blog-content">
                    {!! $blog->description !!}
                </div>

                <hr class="my-5">

                <a href="{{ route('vendor.mc-vendor.blog-mc-vendor-hub') }}" class="text-decoration-none" style="color:#81c408;">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to all blogs
                </a>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-3">

                {{-- Categories --}}
                <div class="card border-0 shadow-sm p-3 mb-4">
                    <h6 class="fw-bold mb-3" style="color:#81c408;">Categories</h6>
                    <div class="category-sidebar">
                        @foreach ($all_categories as $ct)
                            <a href="{{ route('vendor.mc-vendor.blog-mc-vendor-hub.category', $ct->slug) }}">
                                <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($ct->image, asset('storage/app/public/blog/category/') . '/' . $ct->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'blog/category/') }}"
                                    alt="{{ $ct->name }}"
                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}">
                                <span>{{ $ct->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Related Posts --}}
                @if ($related_blogs->count())
                    <div class="card border-0 shadow-sm p-3">
                        <h6 class="fw-bold mb-3" style="color:#81c408;">More Posts</h6>
                        <div class="d-flex flex-column gap-3">
                            @foreach ($related_blogs as $rb)
                                <a href="{{ route('vendor.mc-vendor.blog-mc-vendor-hub.post', $rb->slug) }}"
                                    class="text-decoration-none text-dark">
                                    <div class="related-card d-flex overflow-hidden">
                                        <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rb->image, asset('storage/app/public/blog/') . '/' . $rb->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'blog/') }}"
                                            alt="{{ $rb->title }}"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}">
                                        <div class="p-2">
                                            <p class="mb-0 small fw-medium" style="line-height:1.4;">{{ substr($rb->title, 0, 60) }}{{ strlen($rb->title) > 60 ? '...' : '' }}</p>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($rb->created_at)->format('d M Y') }}</small>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <a href="{{ route('vendor.mc-vendor.blog-mc-vendor-hub') }}" class="d-block mt-3 text-decoration-none fw-semibold" style="color:#81c408;">+ View All</a>
                    </div>
                @endif

            </div>

        </div>
    </div>

    @include('mc-vendor.partials.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</body>
</html>
