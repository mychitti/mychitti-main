 
  @php  $logo = \App\Models\BusinessSetting::where(['key' => 'mcvendor_footer_logo'])->first()->value; @endphp

    <div class="container-fluid bg-dark text-white-50 footer pt-5 ">
        <div class="container p-2">
            <div class="pb-4 mb-2" style="border-bottom: 1px solid rgba(226, 175, 24, 0.5) ;">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="">
                            <img class="" style="width: 130px; margin:0 auto;"  data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($logo, asset('storage/app/public/business/') . '/' . $logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'business/') }}"
                    alt="MCVendorHub">

                        </div>
                    </div> 
                    <div class="col-lg-6">
                        <div class="d-flex gap-4 justify-content-end pt-3">
                            {{-- <a class="text-white text-decoration-none me-2"
                                href="{{ route('blog-mc-vendor-hub') }}">Blogs</a> --}}
                            <a class="text-white text-decoration-none me-2"
                                href="https://mcvendorhub.com/tnc">Terms and Conditions</a>
                                 <a class="text-white text-decoration-none me-2"
                                href="https://mcvendorhub.com/privacy-policy">Privacy Policy</a>
                            <a class="text-white text-decoration-none me-2" href="https://mcvendorhub.com/price-calculator/contact">Contact</a>
                        </div>
                    </div>

                </div> 

            </div>
            <div class="col-12 text-center">
                © {{date('Y')}} MCVENDORHUB. All rights reserved.</div>
        </div>
    </div>