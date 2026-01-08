 @php
 $logo = asset('storage/vendor_login/mc_vendor_hub_logo.png'); @endphp
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
     integrity="sha512-aNSrQdQ1ZqfZC/0kSPSz7jMo1CJz4TqYkz6/yM5cO0u8jPPCZoxg70kC2U6g7q5FdL88Yv3E/8Z0IuB2B+R5GQ=="
     crossorigin="anonymous" referrerpolicy="no-referrer" />

 {{-- <div id="toast" class="toast">This is a toaster notification!</div> --}}

 <nav class="navbar navbar-expand-lg bg-body-tertiary"> 
     <div class="container-fluid">
         <a href="{{ route('home') }}" class="">
             <img class="" style="    width: 135px;margin:0 auto;" src="{{ $logo ?? '' }}">
             {{-- <h1 class="fs-4 px-3" style="display:inline;">{{ $store['name'] }}</h1> --}}
         </a>
         <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup"
             aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
             <span class="navbar-toggler-icon">
                 <i class="fa-solid fa-bars"></i> </span>
         </button>
         <div class="collapse navbar-collapse" style="width:fit-content; justify-content: end;" id="navbarNavAltMarkup">
             <div class="navbar-nav">
                 <a href="{{ route('vendor.mc-vendor.home') }}" class="my-nav-link nav-link ">Home</a>
                 <a href="{{ route('vendor.mc-vendor.home') }}#products_section" class="my-nav-link nav-link ">Products</a>
                 <a href="{{ route('vendor.mc-vendor.price-calculator') }}" class="my-nav-link nav-link ">Price Calculator</a>
                 <a href="{{ route('vendor.mc-vendor.home') }}#faq_section" class="my-nav-link nav-link">FAQs</a>
                 <a href="{{ route('vendor.mc-vendor.home') }}#review_section" class="my-nav-link nav-link">Reviews</a>
                 <a href="https://vendor.mcvendorhub.com/login/store" class="my-nav-link nav-link">Login</a>
                 <a href="https://vendor.mcvendorhub.com/list-your-business" class="my-nav-link nav-link">Signup</a>

             </div>
         </div>
     </div>
 </nav>



 
 <nav class="px-3"
     style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='currentColor'/%3E%3C/svg%3E&#34;);"
     aria-label="breadcrumb">
     <ol class="breadcrumb d-flex align-items-center" style="font-size: 12px;">
         <li class="breadcrumb-item"><a class="fs-6 text-dark" href="{{ route('home') }}">Home</a></li>
         <li class="breadcrumb-item active" aria-current="page">Vendor's Page</li>
     </ol>
 </nav>
