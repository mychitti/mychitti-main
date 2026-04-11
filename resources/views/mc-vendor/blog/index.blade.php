<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Blog — MCVendorHub</title>
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
        .my-nav-link:hover::after, .my-nav-link.active::after { transform: scaleX(1); }
        .blog-card { border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden; transition: box-shadow .2s; height: 100%; }
        .blog-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.1); }
        .blog-card img { height: 200px; object-fit: cover; width: 100%; }
        .blog-card-body { padding: 16px; }
        .category-sidebar a { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f0f0f0; color: #333; text-decoration: none; font-weight: 500; transition: color .2s; }
        .category-sidebar a:hover { color: #81c408; }
        .category-sidebar a.active { color: #81c408; }
        .category-sidebar img { height: 40px; width: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; }
        .btn-mcv { background: #81c408; color: #fff; border: none; padding: 6px 18px; border-radius: 6px; font-size: 14px; }
        .btn-mcv:hover { background: #6aaa06; color: #fff; }
        .page-hero { background: linear-gradient(135deg, #f0f7e6 0%, #e8f5d0 100%); padding: 48px 0 32px; border-bottom: 1px solid #d4edac; }
    </style>
</head>
<body>
    @include('mc-vendor.partials.nav')

    <div class="page-hero">
        <div class="container">
            <h1 class="fw-bold mb-1">Blog</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('vendor.mc-vendor.home') }}" class="text-decoration-none" style="color:#81c408;">Home</a></li>
                    <li class="breadcrumb-item active">Blog</li>
                    @isset($category)
                        <li class="breadcrumb-item active">{{ $category->name }}</li>
                    @endisset
                </ol>
            </nav>
        </div>
    </div>

    <div class="container py-5">
        <div class="row g-4">

            {{-- Blog Grid --}}
            <div class="col-lg-9">
                @isset($category)
                    <h5 class="mb-4 text-muted">Category: <strong>{{ $category->name }}</strong>
                        <a href="{{ route('vendor.mc-vendor.blog-mc-vendor-hub') }}" class="ms-2 text-decoration-none" style="font-size:13px; color:#81c408;">× Clear filter</a>
                    </h5>
                @endisset

                <div class="row g-4">
                    @forelse ($blogs as $b)
                        <div class="col-md-6 col-xl-4">
                            <a href="{{ route('vendor.mc-vendor.blog-mc-vendor-hub.post', $b->slug) }}" class="text-decoration-none text-dark">
                                <div class="blog-card">
                                    <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($b->image, asset('storage/app/public/blog/') . '/' . $b->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'blog/') }}"
                                        alt="{{ substr($b->title, 0, 80) }}"
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}">
                                    <div class="blog-card-body">
                                        <small class="text-muted">{{ $b->cat_name }} &bull; {{ \Carbon\Carbon::parse($b->created_at)->format('d M Y') }}</small>
                                        <h6 class="mt-2 mb-1 fw-semibold" style="min-height:44px;">{{ substr($b->title, 0, 70) }}{{ strlen($b->title) > 70 ? '...' : '' }}</h6>
                                        <p class="text-muted small mb-3" style="min-height:48px;">{{ substr(strip_tags($b->short_description), 0, 110) }}{{ strlen($b->short_description) > 110 ? '...' : '' }}</p>
                                        <button class="btn-mcv">Read More</button>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5">
                            <i class="fa-regular fa-file-lines fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No blog posts found</h5>
                        </div>
                    @endforelse
                </div>

                @if ($blogs->hasPages())
                    <div class="mt-4">
                        {{ $blogs->links() }}
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-3">
                <div class="card border-0 shadow-sm p-3 mb-4">
                    <h6 class="fw-bold mb-3" style="color:#81c408;">Categories</h6>
                    <div class="category-sidebar">
                        <a href="{{ route('vendor.mc-vendor.blog-mc-vendor-hub') }}"
                            class="{{ !isset($category) ? 'active' : '' }}">
                            <span>All Posts</span>
                        </a>
                        @foreach ($all_categories as $ct)
                            <a href="{{ route('vendor.mc-vendor.blog-mc-vendor-hub.category', $ct->slug) }}"
                                class="{{ isset($category) && $category->slug === $ct->slug ? 'active' : '' }}">
                                <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($ct->image, asset('storage/app/public/blog/category/') . '/' . $ct->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'blog/category/') }}"
                                    alt="{{ $ct->name }}"
                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}">
                                <span>{{ $ct->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>

    @include('mc-vendor.partials.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</body>
</html>
