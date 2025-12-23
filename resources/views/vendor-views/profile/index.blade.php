@extends('layouts.vendor.app')

@section('title', translate('messages.profile_settings'))

@push('css_or_js')
@endpush

@section('content')
    <!-- Content -->
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">{{ translate('messages.settings') }}</h1>
                </div>
            </div>
            <!-- End Row -->
        </div>
        <!-- End Page Header -->

        <div class="row">
            <div class="col-lg-12">
                <!-- Navbar -->
                <div class="navbar-vertical navbar-expand-lg mb-1 ">
                    <!-- Navbar Toggle -->
                    <button type="button" class="navbar-toggler btn btn-block btn-white mb-3" aria-label="Toggle navigation"
                        aria-expanded="false" aria-controls="navbarVerticalNavMenu" data-toggle="collapse"
                        data-target="#navbarVerticalNavMenu">
                        <span class="d-flex justify-content-between align-items-center">
                            <span class="h5 mb-0">{{ translate('messages.nav_menu') }}</span>

                            <span class="navbar-toggle-default">
                                <i class="tio-menu-hamburger"></i>
                            </span>

                            <span class="navbar-toggle-toggled">
                                <i class="tio-clear"></i>
                            </span>
                        </span>
                    </button>

                    <div class="d-block d-md-none">
                        <div id="navbarVerticalNavMenu" class="collapse navbar-collapse ">
                            <!-- Navbar Nav -->
                            <ul id="navbarSettings"
                                class="js-sticky-block js-scrollspy navbar-nav navbar-nav-lg nav-tabs card card-navbar-nav">
                                <li class="nav-item">
                                    <a class="nav-link active text-dark" href="javascript:" id="generalSection">
                                        <i class="tio-user-outlined nav-icon"></i>
                                        {{ translate('messages.basic_information') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-dark" href="#passwordDiv" id="passwordSection">
                                        <i class="tio-lock-outlined nav-icon"></i> {{ translate('messages.password') }}
                                    </a>
                                </li>
                                @if (auth('vendor')->check())
                                    @if (0 && \App\CentralLogics\Helpers::get_store_data()->module->id == 6)
                                        <li class="nav-item">
                                            <a class="nav-link text-dark" href="#planDiv" id="planSection">
                                                <i class="tio-calendar-note nav-icon"></i> Subscription Plan
                                            </a>
                                        </li>
                                    @endif
                                    <li class="nav-item">
                                        <a class="nav-link text-dark" href="#servicesDiv" id="">
                                            <i class="tio-calendar-note nav-icon"></i> Services and Categories
                                        </a>
                                    </li>
                                @endif
                            </ul>
                            <!-- End Navbar Nav -->
                        </div>
                    </div>
                </div>

                <form action="{{ env('APP_MODE') != 'demo' ? route('vendor.profile.update') : 'javascript:' }}"
                    method="post" enctype="multipart/form-data" id="vendor-settings-form">
                    @csrf
                    <!-- Card -->
                    <div class="card mb-1 d-flex flex-row-reverse justify-content-between px-2" id="generalDiv">
                        <!-- Avatar -->
                        <label class="avatar avatar-xxl avatar avatar-border-lg avatar-uploader profile-cover-avatar"
                            for="avatarUploader">
                            <img id="viewer" data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                class="avatar-img onerror-image"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper(auth('vendor')->check() ? auth('vendor')->user()->image : auth('vendor_employee')->user()->image, asset('storage/app/public/vendor/') . '/' . (auth('vendor')->check() ? auth('vendor')->user()->image : auth('vendor_employee')->user()->image), asset('public/assets/admin/img/160x160/img1.jpg'), 'vendor/') }}"
                                alt="Image">
                            {{-- accept="image/*,android/allowCamera"  --}}
                            <input type="file" name="image" class="js-file-attach avatar-uploader-input"
                                id="customFileEg1" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                            <label class="avatar-uploader-trigger" for="customFileEg1">
                                <i class="tio-edit avatar-uploader-icon shadow-soft"></i>
                            </label>
                        </label>

                        <div class="d-none d-md-block ml-3">
                            <ul id="navbarSettings2"
                                class=" js-sticky-block js-scrollspy navbar-nav navbar-nav-lg nav-tabs border-0  pt-0">
                                <li class="nav-item">
                                    <a class="nav-link active text-dark py-1" href="javascript:" id="generalSection">
                                        <i class="tio-user-outlined nav-icon"></i>
                                        {{ translate('messages.basic_information') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-dark py-1" href="#passwordDiv" id="passwordSection">
                                        <i class="tio-lock-outlined nav-icon"></i> {{ translate('messages.password') }}
                                    </a>
                                </li>
                                @if (auth('vendor')->check())
                                    @if (0 && \App\CentralLogics\Helpers::get_store_data()->module->id == 6)
                                        <li class="nav-item">
                                            <a class="nav-link text-dark py-1" href="#planDiv" id="planSection">
                                                <i class="tio-calendar-note nav-icon"></i> Subscription Plan
                                            </a>
                                        </li>
                                    @endif
                                    <li class="nav-item">
                                        <a class="nav-link text-dark py-1" href="#servicesDiv" id="">
                                            <i class="tio-calendar-note nav-icon"></i> Services and Categories
                                        </a>
                                    </li>
                                @endif
                            </ul>
                            <!-- End Avatar -->
                        </div>
                    </div>
                    <!-- End Card -->

                    <!-- Card -->
                    <div class="card mb-1 ">
                        <div class="card-header">
                            <h2 class="card-title h4"><i class="tio-info"></i>
                                {{ translate('messages.basic_information') }}
                            </h2>
                        </div>

                        <!-- Body -->
                        <div class="card-body row   ">
                            <!-- Form -->
                            <!-- Form Group -->
                            <div class=" col-md-4 form-group">
                                <label for="firstNameLabel" class=" input-label">{{ translate('messages.full_name') }} <i
                                        class="tio-help-outlined text-body ml-1" data-toggle="tooltip" data-placement="top"
                                        title="Display name"></i></label>

                                <div class="">
                                    <div class="input-group input-group-sm-down-break">
                                        <input type="text" class="form-control" name="f_name" id="firstNameLabel"
                                            placeholder="{{ translate('messages.your_first_name') }}"
                                            aria-label="{{ translate('messages.your_first_name') }}"
                                            value="{{ auth('vendor')->check() ? auth('vendor')->user()->f_name : auth('vendor_employee')->user()->f_name }}">
                                        <input type="text" class="form-control" name="l_name" id="lastNameLabel"
                                            placeholder="{{ translate('messages.your_last_name') }}"
                                            aria-label="{{ translate('messages.your_last_name') }}"
                                            value="{{ auth('vendor')->check() ? auth('vendor')->user()->l_name : auth('vendor_employee')->user()->l_name }}">
                                    </div>
                                </div>
                            </div>
                            <!-- End Form Group -->

                            <!-- Form Group -->
                            <div class=" col-md-4 form-group">
                                <label for="phoneLabel" class="input-label">{{ translate('messages.phone') }} <span
                                        class="input-label-secondary">({{ translate('messages.optional') }})</span></label>

                                <div class="">
                                    <input type="text" class="js-masked-input form-control" name="phone"
                                        id="phoneLabel" placeholder="+x(xxx)xxx-xx-xx" aria-label="+(xxx)xx-xxx-xxxxx"
                                        value="{{ auth('vendor')->check() ? auth('vendor')->user()->phone : auth('vendor_employee')->user()->phone }}"
                                        data-hs-mask-options='{
                                           "template": "+(880)00-000-00000"
                                         }'>
                                </div>
                            </div>
                            <!-- End Form Group -->

                            <div class=" col-md-4 form-group">
                                <label for="newEmailLabel" class=" input-label">{{ translate('messages.email') }}</label>

                                <div class="">
                                    <input type="email" class="form-control" name="email" id="newEmailLabel"
                                        value="{{ auth('vendor')->check() ? auth('vendor')->user()->email : auth('vendor_employee')->user()->email }}"
                                        placeholder="{{ translate('messages.enter_new_email_address') }}"
                                        aria-label="{{ translate('messages.enter_new_email_address') }}">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mx-3 w-100">
                                <button type="button" data-id="vendor-settings-form"
                                    data-message="{{ translate('you_want_to_update_user_info') }}"
                                    class="btn btn-primary {{ env('APP_MODE') != 'demo' ? 'form-alert' : 'call-demo' }}">{{ translate('messages.Save_changes') }}</button>
                            </div>

                            <!-- End Form -->
                        </div>
                        <!-- End Body -->
                    </div>
                    <!-- End Card -->
                </form>

                <!-- Card -->
                <div id="passwordDiv" class="card mb-1">
                    <div class="card-header">
                        <h2 class="card-title h4">
                            <i class="tio-lock"></i>
                            <span>{{ translate('messages.change_your_password') }}</span>
                        </h2>
                    </div>

                    <!-- Body -->
                    <div class="card-body">
                        <!-- Form -->
                        <form id="changePasswordForm"
                            action="{{ env('APP_MODE') != 'demo' ? route('vendor.profile.settings-password') : 'javascript:' }}"
                            method="post" enctype="multipart/form-data" class="row align-items-end">
                            @csrf

                            <!-- Form Group -->
                            <div class="col-md-4 form-group mb-0">
                                <label for="newPassword"
                                    class=" input-label">{{ translate('messages.new_password') }}<span
                                        class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                        data-original-title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"><img
                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                            alt="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"></span></label>

                                <div class="">
                                    <input type="password" class="js-pwstrength form-control" name="password"
                                        id="newPassword" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                        title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"
                                        placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}"
                                        aria-label="8+ characters required"
                                        data-hs-pwstrength-options='{
                                           "ui": {
                                             "container": "#changePasswordForm",
                                             "viewports": {
                                               "progress": "#passwordStrengthProgress",
                                               "verdict": "#passwordStrengthVerdict"
                                             }
                                           }
                                         }'
                                        required>

                                    <p id="passwordStrengthVerdict" class="form-text mb-0"></p>

                                    <div id="passwordStrengthProgress"></div>
                                </div>
                            </div>
                            <!-- End Form Group -->

                            <!-- Form Group -->
                            <div class="col-md-4 form-group mb-0">
                                <label for="confirmNewPasswordLabel"
                                    class="input-label">{{ translate('messages.confirm_password') }}</label>

                                <div class="">
                                    <div>
                                        <input type="password" class="form-control" name="confirm_password"
                                            id="confirmNewPasswordLabel" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                            title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"
                                            placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}"
                                            aria-label="8+ characters required" required>
                                        <p id="" class="form-text mb-0"></p>

                                    </div>
                                </div>
                            </div>
                            <!-- End Form Group -->

                            <div class="col-md-4 form-group mb-0 pt-2 border rounded ">
                                <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                    for="gst_status">
                                    <span>Two Factor Authentication</span>
                                    <form action="">
                                        @csrf
                                        <input type="checkbox" class="toggle-switch-input" name="gst_status"
                                            id="gst_status" onchange="this.form.submit()" value="1">
                                    </form>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                    <p id="" class="form-text mb-0"></p>
                                </label>
                            </div>

                            <div class="d-flex justify-content-end mx-3 w-100 mt-2">
                                <button type="button" data-id="changePasswordForm"
                                    data-message="{{ translate('messages.want_to_update_password') }}"
                                    class="btn btn-primary {{ env('APP_MODE') != 'demo' ? 'form-alert' : 'call-demo' }}">{{ translate('messages.Save_changes') }}</button>
                            </div>
                        </form>
                        @if (auth('vendor_employee')->check())
                            @if (!$data['resign'])
                                <button class="btn btn-danger " type="button" data-toggle="modal"
                                    data-target="#exampleModal"><i class="tio-remove-circle"></i>
                                    {{ translate('messages.Resign') }}</button>
                                <div class="modal fade" id="exampleModal" tabindex="-1"
                                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLabel">Resignation</h5>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <form action="{{ route('vendor.employee.resign') }}">
                                                    @csrf
                                                    <label for="reason">Reason</label>
                                                    <textarea name="reason" class="form-control" placeholder="Start typing..." required id="reason"></textarea>
                                                    {{-- <label for="password">Password <small>(For security reason)</small></label>
                                                <input type="password" name="password" required class="form-control my-1"> --}}
                                                    <div class="d-flex justify-content-end w-100">
                                                        <button class="btn btn-danger">Proceed</button>
                                                    </div>
                                                </form>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            @else
                                <h4 class="text-danger">Resignation Submitted</h4>
                            @endif

                        @endif
                        <!-- End Form -->
                    </div>
                    <!-- End Body -->
                </div>
                <!-- End Card -->
                @if (auth('vendor')->check())
                    <div id="servicesDiv" class="card mb-1">
                        <div class="card-header">
                            <h2 class="card-title h4">
                                <i class="tio-calendar-note"></i>
                                <span>{{ $store_data->module_id == 6 ? 'Services and ' : '' }}Categories</span>
                            </h2>
                        </div>

                        <!-- Body -->
                        <div class="card-body">
                            <form class="row" action="{{ route('vendor.category.update-selected') }}" method="post">
                                @csrf
                                @if ($store_data->module_id == 6)
                                    <div class=" col-md-6 form-group">
                                        <div class="form-group mb-1">
                                            <label class="input-label" id="" for="other_verification">Category
                                                1<span>*</span></label>
                                            <select name="category_1" data-id="1" required
                                                class="category_select form-control __form-control js-select2-custom js-example-basic-single"
                                                data-placeholder="Category">
                                                <option value=""></option>
                                                @foreach ($module_categories as $cat)
                                                    <option {{ $cat->id == $store_data['category_1'] ? 'selected' : '' }}
                                                        value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class=" col-md-6 form-group subcategory_1">
                                        <div class="form-group mb-1">
                                            <label class="input-label" id="" for="other_verification">Services
                                                1</label>
                                            <select name="services_1[]" multiple="multiple"
                                                class="select_subcategory_1 form-control __form-control js-select2-custom js-example-basic-multiple"
                                                data-placeholder="Subcategory">
                                                <option value=""></option>
                                                @foreach ($items_1 as $sc)
                                                    <option
                                                        {{ in_array($sc->id, explode(',', $store_data->services_1)) ? 'selected' : '' }}
                                                        value="{{ $sc->id }}">{{ $sc->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class=" col-md-6 form-group">
                                        <label class="input-label" id="" for="other_verification">Category 2
                                            <span>(optional)</span></label>
                                        <select name="category_2" data-id="2"
                                            class="category_select form-control __form-control js-select2-custom js-example-basic-single"
                                            data-placeholder="Category">
                                            <option value=""></option>
                                            @foreach ($module_categories as $cat)
                                                <option {{ $cat->id == $store_data['category_2'] ? 'selected' : '' }}
                                                    value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class=" col-md-6 form-group subcategory_2">
                                        <div class="form-group mb-1">
                                            <label class="input-label" id="" for="other_verification">Services
                                                2</label>
                                            <select name="services_2[]" multiple="multiple" id=""
                                                class="category_select select_subcategory_2 form-control __form-control js-select2-custom js-example-basic-multiple"
                                                data-placeholder="Subcategory">
                                                <option value=""></option>
                                                @foreach ($items_2 as $sc)
                                                    <option
                                                        {{ in_array($sc->id, explode(',', $store_data->services_2)) ? 'selected' : '' }}
                                                        value="{{ $sc->id }}">{{ $sc->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                @else
                                    <div class="categroy_set_shop">
                                        <div class="form-group shop_categories">
                                            <div class="form-group mb-1">
                                                <label class="input-label" id=""
                                                    for="shop_categories">Categories
                                                    (max 20)</label>
                                                <select name="shop_categories[]" multiple="multiple" id="shop_categories"
                                                    class=" form-control __form-control  select_2_max_20"
                                                    data-placeholder="Categories">
                                                    <option value=""></option>
                                                    @foreach ($module_categories as $cat)
                                                        <option
                                                            {{ in_array($cat->id, explode(',', $store_data->shop_categories)) ? 'selected' : '' }}
                                                            value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="d-flex justify-content-end mx-3 w-100">

                                    <button class="btn btn-primary ">{{ translate('messages.Save_changes') }}</button>
                                </div>
                            </form>
                        </div>
                        <!-- End Body -->
                    </div>
                @endif

                <!-- Sticky Block End Point -->
                <div id="stickyBlockEndPoint"></div>
            </div>
        </div>
        <!-- End Row -->
    </div>

    <!-- End Content -->

@endsection

@push('script_2')


    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/profile-index.js"></script>


    <script>
        $('.category_select').on('change', function() {
            var cat_id = $(this).val()
            var dataid = $(this).attr('data-id');

            //fetchsubcategory
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.get({
                url: "{{ route('fetch-subcategory') }}",
                data: {
                    cat_id: cat_id
                },
                success: function(data) {
                    console.log(data)
                    if (data) {
                        if (data.categories.length) {
                            var html = '';
                            data.categories.forEach(element => {
                                html += '<option value="' + element.id + '">' + element.name +
                                    '</option>';
                            });
                            $(".subcategory_" + dataid).show()
                            $(".select_subcategory_" + dataid).html(html)
                        } else {
                            $(".subcategory_" + dataid).hide()
                            $(".select_subcategory_" + dataid).html('')
                            $(".select_subcategory_" + dataid).val('')
                        }
                    }
                },
            });
        })

        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function(e) {
                    $('#viewer').attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function() {
            readURL(this);
        });
    </script>

    <script>
        $("#generalSection").click(function() {
            $("#passwordSection").removeClass("active");
            $("#planSection").removeClass("active");
            $("#generalSection").addClass("active");
            $('html, body').animate({
                scrollTop: $("#generalDiv").offset().top
            }, 2000);
        });

        $("#passwordSection").click(function() {
            $("#generalSection").removeClass("active");
            $("#planSection").removeClass("active");
            $("#passwordSection").addClass("active");
            $('html, body').animate({
                scrollTop: $("#passwordDiv").offset().top
            }, 2000);
        });
        $('#planSection').click(function() {
            $("#generalSection").removeClass("active");
            $("#passwordSection").removeClass("active");
            $("#planSection").addClass("active");
            $('html, body').animate({
                scrollTop: $("#planDiv").offset().top
            }, 2000);
        })
    </script>
    @if (request('flag') && request('flag') == 'success')
        <script>
            $(document).ready(function() {
                toastr.success('Plan purchased successfully!', 'Success');

                // Remove 'flag' and 'token' from the URL
                const url = new URL(window.location);
                url.searchParams.delete('flag'); // Remove flag
                url.searchParams.delete('token'); // Remove token

                // Update the URL without reloading the page
                window.history.replaceState({}, '', url);
            });
        </script>
    @endif
@endpush
