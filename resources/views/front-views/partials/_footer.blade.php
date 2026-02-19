<!-- Footer Start -->
<style>
    .app_dwnld_div img {
        width: 150px;
    }

    .app_dwnld_div {
        text-align: center;
    }
</style>
{{-- common footer  --}}

@if (!Route::is('store.details') && !Request::is('store-terms-and-conditions') && !request()->attributes->get('is_store_domain'))
    <div class="container-fluid bg-dark text-white-50 footer pt-5 ">
        <div class="container p-2 py-5">
            <div class="pb-4 mb-4" style="border-bottom: 1px solid rgba(226, 175, 24, 0.5) ;">
                <div class="row">
                    <div class="col-lg-3"> 
                        <a href="#">
                            @php($store_logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first()->value)

                            <img style="max-width:200px;"
                                class="navbar-brand-logo initial--36 onerror-image onerror-image"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                src="{{asset('storage/app/public/business/' . $store_logo)}}" alt="Logo">
                                
                            @if (Config::get('module.current_module_id') == 6)
                                <p class="text-secondary mb-0">Top Services</p>
                            @else
                                <p class="text-secondary mb-0">Top Products</p>
                            @endif
                        </a>
                    </div>
                    <div class="col-lg-6">
                        <!-- <div class="position-relative mx-auto">
                        <input class="form-control border-0 w-100 py-3 px-4 rounded-pill" type="email" placeholder="Your Email">
                        <button type="submit" class="btn btn-primary border-0 border-secondary py-3 px-4 position-absolute rounded-pill text-white" style="top: 0; right: 0;">Subscribe Now</button>
                    </div> -->
                    </div>
                    <div class="col-lg-3">
                        <div class="d-flex justify-content-end pt-3">
                            <a class="btn  btn-outline-secondary me-2 btn-md-square rounded-circle"
                                href="{{ _social_media('twitter') }}"><i class="fab fa-twitter"></i></a>
                            <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle"
                                href="{{ _social_media('facebook') }}"><i class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle"
                                href="{{ _social_media('pinterest') }}"><i class="fab fa-pinterest"></i></a>
                            <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle"
                                href="{{ _social_media('instagram') }}"><i class="fab fa-instagram"></i></a>
                            <a class="btn btn-outline-secondary btn-md-square rounded-circle"
                                href="{{ _social_media('linkedin') }}"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row ">
                <div class="col-lg-3 col-md-6">
                    <div class="footer-item">
                        <h4 class="text-light mb-3">Why People Like us!</h4>
                        <p class="mb-4">
                            {{ substr(strip_tags(html_entity_decode(trim(_aboutText()))), 0, 100) }}...
                        </p>
                        <a href="{{ route('about-us') }}"
                            class="btn border-secondary py-2 px-4 rounded-pill text-primary">Read More</a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="d-flex flex-column text-start footer-item">
                        <h4 class="text-light mb-3">About Us</h4>
                        <a href="{{ route('contact') }}" class="btn-link">Contact Us</a>
                        <a class="btn-link" href="{{ route('about-us') }}">About Us</a>
                        <a href="{{ route('blog') }}" class="btn-link">Blogs</a>

                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="d-flex flex-column text-start footer-item">
                        <h4 class="text-light mb-3">Customer Support</h4>
                        <a href="{{route('faq')}}" class="btn-link">FAQs</a>
                        <p>Email: <a
                                href="mailto:{{ _footerInfo('email_address') }}">{{ _footerInfo('email_address') }}</a>
                        </p>
                        <p>TollFree number: <a href="javascript:;">{{ _footerInfo('phone') }}</a></p>
                        <p>CIN number: {{ _footerInfo('cin_number') }}</p>

                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="d-flex flex-column text-start footer-item">
                        <h4 class="text-light mb-3">Consumer Policy</h4>
                        <a class="btn-link" href="{{ route('user-terms-and-conditions') }}">Terms & Condition</a>
                        <a class="btn-link" href="{{ route('privacy-policy') }}">Privacy Policy</a>
                        <a class="btn-link" href="{{ route('cancellation-policy') }}">Cancellation Policy</a>
                        <a class="btn-link" href="{{ route('refund-policy') }}">Refund Policy</a>
                        @if (Config::get('module.current_module_id') == 5)
                            <a class="btn-link" href="{{ route('shipping-policy') }}">Shipping Policy</a>
                        @endif
                            <a class="btn-link" href="{{ route('disclaimer') }}">Disclaimer</a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="d-flex flex-column text-start footer-item">
                        <h4 class="text-light mb-3">Account</h4>
                        <a href="{{ !auth('web')->user() ? route('user-login') : route('dashboard') }}"
                            class="btn-link">
                            {{ auth('web')->user() ? 'My Account' : 'Login/Register' }}
                        </a>
                        <a href="{{ route('new-store.create') }}" class="btn-link">List Your Business For Free</a>
                        @if (auth('web')->user())
                            <a class="btn-link" href="{{ route('cart') }}">My Cart</a>
                            @if (_cartCount())
                                <a href="{{ route('checkout') }}" class="btn-link">Checkout</a>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="footer-item">

                        <h5 class="text-light">Download Android Apps</h5>
                        <div class="d-flex flex-wrap text-light">
                           
                           
                            <div class="app_dwnld_div">
                                <a class="text-white" target="_blank"
                                    href="https://play.google.com/store/apps/details?id=com.mychittiappuser">
                                    <img src="{{ asset('storage/app/public/util/android_app_download.png') }}">
                                    <p>User App</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@elseif(isset($store))
    <div class="container-fluid bg-dark text-white-50 footer pt-5 ">
        <div class="container p-2">
            <div class="pb-4 mb-4" style="border-bottom: 1px solid rgba(226, 175, 24, 0.5) ;">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="">
                            <img style="width:70px;" class="img-fluid"
                                src="{{ asset('storage/app/public/store') . '/' . $store->logo }}">
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="d-flex justify-content-center pt-3">
                            <a class="btn  btn-outline-secondary me-2 btn-md-square rounded-circle"
                                href="{{ _vendorSocialMedia('twitter_url', $store->id) }}"><i
                                    class="fab fa-twitter"></i></a>
                            <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle"
                                href="{{ _vendorSocialMedia('fb_url', $store->id) }}"><i
                                    class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle"
                                href="{{ _vendorSocialMedia('pinterest_url', $store->id) }}"><i
                                    class="fab fa-pinterest"></i></a>
                            <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle"
                                href="{{ _vendorSocialMedia('insta_url', $store->id) }}"><i
                                    class="fab fa-instagram"></i></a>
                            <a class="btn btn-outline-secondary btn-md-square rounded-circle"
                                href="{{ _vendorSocialMedia('linkedin_url', $store->id) }}"><i
                                    class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div>
                            <p style="text-align: end;">
                                <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseTNC" aria-expanded="false"
                                    aria-controls="collapseTNC">
                                    Terms And Conditions
                                </button>
                            </p>
                            <div class="collapse" id="collapseTNC">
                                <div class="card card-body text-dark">
                                  {!! _vendorTandC($store->id) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
<!-- common footer end -->

{{-- store footer  --}}
{{-- store footer end --}}



<!-- Copyright Start -->
<div class="container-fluid copyright bg-dark py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <span class="text-light"><a href="#"><i class="fas fa-copyright text-light me-2"></i>My
                        Chitti</a>, All right reserved.</span>
            </div>
            <div class="col-md-6 my-auto text-center text-md-end text-white">
                <!--/*** This template is free as long as you keep the below author’s credit link/attribution link/backlink. ***/-->
                <!--/*** If you'd like to use the template without the below author’s credit link/attribution link/backlink, ***/-->
                <!--/*** you can purchase the Credit Removal License from "https://htmlcodex.com/credit-removal". ***/-->

            </div>
        </div>
    </div>
</div>
<!-- Copyright End -->



<!-- Back to Top -->
<a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top"><i
        class="fa fa-arrow-up"></i></a>
