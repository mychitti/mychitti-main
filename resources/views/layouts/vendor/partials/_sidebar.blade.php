<style>
    .show_on_hover {
        position: absolute;
        top: 0px;
        left: 60px;
        background: #005555;
        border-radius: 10px;
        background-color: #005555;
    }

    .navbar-vertical-aside-has-menu {
        margin: 5px 0px;
    }

    .nav-link-icon {
        width: 27px;
        margin-right: 3px;
    }
</style>
<div id="sidebarMain" class="d-none">
    <aside
        class="js-navbar-vertical-aside navbar navbar-vertical-aside navbar-vertical navbar-vertical-fixed navbar-expand-xl navbar-bordered">
        <div class="navbar-vertical-container">
            <div class="navbar-brand-wrapper justify-content-between">
                <!-- Logo -->
                @php($store_data = \App\CentralLogics\Helpers::get_store_data())
{{asset('storage/store/') . '/' . $store_data->logo}}
                @php($isHospital = strtolower($store_data->business_type ?? '') === 'hospital')
                <a class="navbar-brand" href="{{ route('vendor.dashboard') }}" aria-label="Front">
                    <img class="navbar-brand-logo initial--36  onerror-image"
                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_data->logo, asset('storage/store/') . '/' . $store_data->logo, asset('public/assets/admin/img/160x160/img2.jpg'), 'store/') }}"
                        alt="Logo">
                    <img class="navbar-brand-logo-mini initial--36 onerror-image"
                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_data->logo, asset('storage/store/') . '/' . $store_data->logo, asset('public/assets/admin/img/160x160/img2.jpg'), 'store/') }}"
                        alt="Logo">
                </a>
                <b class="text-truncate nav_store_title" style="color: black">{{ $store_data->name }}</b>
                <!-- End Logo -->

                <!-- Navbar Vertical Toggle -->
                <button type="button"
                    class="js-navbar-vertical-aside-toggle-invoker navbar-vertical-aside-toggle btn btn-icon btn-xs btn-ghost-dark">
                    <i class="tio-clear tio-lg"></i>
                </button>
                <!-- End Navbar Vertical Toggle -->

                <div class="navbar-nav-wrap-content-left">
                    <!-- Navbar Vertical Toggle -->
                    <button type="button" class="js-navbar-vertical-aside-toggle-invoker close">
                        <i class="tio-first-page navbar-vertical-aside-toggle-short-align" data-toggle="tooltip"
                            data-placement="right" title="Collapse"></i>
                        <i class="tio-last-page navbar-vertical-aside-toggle-full-align"
                            data-template='<div class="tooltip d-none d-sm-block" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'></i>
                    </button>
                    <!-- End Navbar Vertical Toggle -->
                </div>

            </div>

            <!-- Content -->
            {{-- bg--005555 --}}
            <div class="navbar-vertical-content text-capitalize bg-white" id="navbar-vertical-content">
                {{-- <form class="sidebar--search-form">
                    <div class="search--form-group">
                        <button type="button" class="btn"><i class="tio-search"></i></button>
                        <input type="text" class="form-control form--control"
                            placeholder="{{ translate('messages.Search Menu...') }}" id="search-sidebar-menu">
                    </div>
                </form> --}}
                <ul class="navbar-nav navbar-nav-lg nav-tabs">
                
                    @include(strtolower($store_data->business_type ?? '') === 'hospital'
                            ? 'layouts.vendor.partials._sidebar_menu_hospital'
                            : 'layouts.vendor.partials._sidebar_menu_default', ['store_data' => $store_data])
                </ul>
            </div> 
            <!-- End Content -->
        </div>
    </aside>
</div>

<div id="sidebarCompact" class="d-none">

</div>

@push('script_2')
    <script>
        $(window).on('load', function() {
            if ($(".navbar-vertical-content li.active").length) {
                $('.navbar-vertical-content').animate({
                    scrollTop: $(".navbar-vertical-content li.active").offset().top - 150
                }, 10);
            }
        });

        var $rows = $('#navbar-vertical-content li');
        $('#search-sidebar-menu').keyup(function() {
            var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();

            $rows.show().filter(function() {
                var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
                return !~text.indexOf(val);
            }).hide();
        });
    </script>
@endpush
