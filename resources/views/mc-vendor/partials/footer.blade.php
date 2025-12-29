 
 @php
    $logo = asset('storage/vendor_login/mc_vendor_hub_logo.png'); @endphp
    <div class="container-fluid bg-dark text-white-50 footer pt-5 ">
        <div class="container p-2">
            <div class="pb-4 mb-2" style="border-bottom: 1px solid rgba(226, 175, 24, 0.5) ;">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="">
                            <img class="" style="width: 130px; margin:0 auto;" src="{{ $logo ?? '' }}">

                        </div>
                    </div> 
                    <div class="col-lg-6">
                        <div class="d-flex gap-4 justify-content-end pt-3">
                            {{-- <a class="text-white text-decoration-none me-2"
                                href="{{ route('blog-mc-vendor-hub') }}">Blogs</a> --}}
                            <a class="text-white text-decoration-none me-2"
                                href="{{ route('vendor.mc-vendor.mc-vendor-hub-tnc') }}">Terms and Conditions</a>
                                 <a class="text-white text-decoration-none me-2"
                                href="{{ route('vendor.mc-vendor.mc-vendor-hub-pp') }}">Privacy Policy</a>
                            <a class="text-white text-decoration-none me-2" href="{{ route('vendor.mc-vendor.contact') }}">Contact</a>
                        </div>
                    </div>

                </div> 

            </div>
            <div class="col-12 text-center">
                © {{date('Y')}} MCVENDORHUB. All rights reserved.</div>
        </div>
    </div>