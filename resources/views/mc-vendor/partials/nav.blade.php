 @php  $logo = \App\Models\BusinessSetting::where(['key' => 'mcvendor_logo'])->first()?->value; @endphp
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
     integrity="sha512-aNSrQdQ1ZqfZC/0kSPSz7jMo1CJz4TqYkz6/yM5cO0u8jPPCZoxg70kC2U6g7q5FdL88Yv3E/8Z0IuB2B+R5GQ=="
     crossorigin="anonymous" referrerpolicy="no-referrer" />

 {{-- <div id="toast" class="toast">This is a toaster notification!</div> --}}

 <nav class="navbar navbar-expand-lg bg-body-tertiary"> 
     <div class="container-fluid">
         <a href="https://mcvendorhub.com/price-calculator" class="">
             <img class="" style="    width: 135px;margin:0 auto;"  data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($logo, asset('storage/app/public/business/') . '/' . $logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'business/') }}"
                    alt="MCVendorHub">
             {{-- <h1 class="fs-4 px-3" style="display:inline;">{{ $store['name'] }}</h1> --}}
         </a>
         <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup"
             aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
             <span class="navbar-toggler-icon">
                 <i class="fa-solid fa-bars"></i> </span>
         </button>
         <div class="collapse navbar-collapse" style="width:fit-content; justify-content: end;" id="navbarNavAltMarkup">
             <div class="navbar-nav">
                 <a href="https://mcvendorhub.com" class="my-nav-link nav-link ">Home</a>
                 <a href="https://mcvendorhub.com/#products_section" class="my-nav-link nav-link ">Products</a>
                 <a href="https://mcvendorhub.com/price-calculator" class="my-nav-link nav-link ">Price Calculator</a>
                 <a href="{{ mcv('vendor.mc-vendor.blog-mc-vendor-hub') }}" class="my-nav-link nav-link {{ Request::is('blog-mc-vendor-hub*') ? 'active' : '' }}">Blogs</a>
                 <a href="https://mcvendorhub.com/#faq_section" class="my-nav-link nav-link">FAQs</a>
                 <a href="https://mcvendorhub.com/#review_section" class="my-nav-link nav-link">Reviews</a>
                 <a href="https://vendor.mcvendorhub.com/login" class="my-nav-link nav-link">Login</a>
                 <a href="{{ _vendorSignupUrl() }}" class="my-nav-link nav-link">Signup</a>
             </div>
         </div>
     </div>
 </nav>

