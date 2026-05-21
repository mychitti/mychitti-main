@extends('layouts.vendor.app')

@section('title', translate('messages.profile_settings'))

@push('css_or_js')
    <style>
        .vendor-profile-wrapper {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .05);
            padding: 22px 22px 0 22px;
        }

        .vendor-profile-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 18px;
        }

        .vendor-profile-name {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .vendor-profile-role {
            color: #ff4b55;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .vendor-profile-line {
            color: #677788;
            font-size: 14px;
            margin-bottom: 6px;
        }


        .vendor-profile-avatar img {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f1f3f6;
        }

        .vendor-profile-avatar .camera-btn {
            position: absolute;
            right: -5px;
            top: 5px;
            background: #fff;
            border-radius: 50%;
            padding: 6px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .15);
            cursor: pointer;
        }

        .vendor-profile-footer {
            border-top: 1px solid #eef0f4;
            padding: 12px 0;
            display: flex;
            gap: 25px;
            font-size: 14px;
            color: #677788;
        }

        .vendor-mini-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            height: 100%;
        }

        .vendor-mini-title {
            font-weight: 600;
            margin-bottom: 8px;
        }

        @media (max-width: 767px) {
            .vendor-profile-wrapper {
                padding: 16px 16px 0 16px;
            }

            .vendor-profile-top {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .vendor-profile-avatar {
                order: -1;
                margin-bottom: 14px;
            }

            .vendor-profile-avatar img {
                width: 90px;
                height: 90px;
            }

            .vendor-profile-name {
                font-size: 17px;
            }

            .vendor-profile-role {
                margin-bottom: 8px;
            }

            .vendor-profile-line {
                font-size: 13px;
            }

            .vendor-profile-footer {
                gap: 14px;
                flex-wrap: wrap;
            }

            .dashboard-options-row {
                flex-direction: column !important;
            }

            .dashboard-options-row > div {
                min-width: 0 !important;
                flex: none !important;
                width: 100% !important;
            }
        }
    </style>
@endpush


@section('content')

    <div class="content container-fluid" style="max-width: 1000px;    margin-left: 0;">

        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">{{ translate('messages.settings') }}</h1>
                </div>
            </div>
        </div>

        <div class="vendor-profile-wrapper mb-4">

            <div class="vendor-profile-top">

                <div>
                    <div class="vendor-profile-name">
                        {{ auth('vendor')->check()
                            ? auth('vendor')->user()->f_name . ' ' . auth('vendor')->user()->l_name
                            : auth('vendor_employee')->user()->f_name . ' ' . auth('vendor_employee')->user()->l_name }}
                    </div>

                    <div class="vendor-profile-role">Founder</div>

                    <div class="vendor-profile-line">
                        <i class="tio-call"></i>
                        {{ auth('vendor')->check() ? auth('vendor')->user()->phone : auth('vendor_employee')->user()->phone }}
                    </div>

                    <div class="vendor-profile-line">
                        <i class="tio-email"></i>
                        {{ auth('vendor')->check() ? auth('vendor')->user()->email : auth('vendor_employee')->user()->email }}
                    </div>
                    <div class="vendor-profile-line">
                        <i class="tio-calendar-month"></i>
                        Year of Establish : {{ \Carbon\Carbon::parse($store->created_at)->format('Y') }}
                    </div>
                    <div class="vendor-profile-line">
                        <i class="tio-lock"></i>
                        Password: ****** &nbsp;
                        <a type="button" class="text-danger" data-toggle="modal" data-target="#changePasswordModal">
                            Change Password
                        </a>
                    </div>

                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-primary mr-2" data-toggle="modal"
                            data-target="#editProfileModal">
                            <i class="tio-edit"></i> Edit profile
                        </button>

                        {{-- <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal"
                            data-target="#changePasswordModal">
                            <i class="tio-lock"></i> Change password
                        </button> --}}
                    </div>
                </div>

                <div class="vendor-profile-avatar">
                    <img
                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                            auth('vendor')->check() ? auth('vendor')->user()->image : auth('vendor_employee')->user()->image,
                            asset('storage/app/public/vendor/') .
                                '/' .
                                (auth('vendor')->check() ? auth('vendor')->user()->image : auth('vendor_employee')->user()->image),
                            asset('public/assets/admin/img/160x160/img1.jpg'),
                            'vendor/',
                        ) }}">

                    {{-- <label for="customFileEg1" class="camera-btn">
                        <i class="tio-camera"></i>
                    </label> --}}
                </div>

            </div>
        </div>
{{-- 
        <div class="text-right mb-2">
            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#aboutStoreModal">
                <i class="tio-edit"></i> Edit
            </button>
        </div> --}}

        <div class="row mb-4">

            <div class="col-md-6 mb-3 mb-md-0">
                <div class="vendor-mini-card">
                    <div class="vendor-mini-title">
                        <i class="tio-light-on"></i> Why We Started <a class="text-danger cursor-pointer" data-toggle="modal" data-target="#aboutStoreModal"><i class="tio-edit"></i></a> 
                    </div>
                    <div class="text-muted">
                        {{ $store->vendor?->why_we_started ?? '' }}
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="vendor-mini-card">
                    <div class="vendor-mini-title">
                        <i class="tio-light-on"></i> Our Idea <a class="text-danger cursor-pointer" data-toggle="modal" data-target="#aboutStoreModal"><i class="tio-edit"></i></a> 
                    </div>
                    <div class="text-muted">
                        {{ $store->vendor?->our_idea ?? '—' }}
                    </div>

                </div>
            </div>

        </div>


    </div>


    {{-- =========================
    BASIC INFO MODAL
========================= --}}
    <form action="{{ env('APP_MODE') != 'demo' ? route('vendor.profile.update') : 'javascript:' }}" method="post"
        enctype="multipart/form-data" id="vendor-settings-form">
        @csrf

        <div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('messages.basic_information') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body p-0">

                        <div class="card mb-0 profile-section-card">
                            {{-- <div class="card-header">
                                <h2 class="card-title h4">
                                    <i class="tio-info"></i>
                                    {{ translate('messages.basic_information') }}
                                </h2>
                            </div> --}}

                            <div class="card-body row">
                                <div class="col-md-9 row">
                                    <div class="col-md-6 form-group">
                                        <label class="input-label">{{ translate('messages.full_name') }}</label>

                                        <div class="input-group input-group-sm-down-break">
                                            <input type="text" class="form-control" name="f_name"
                                                placeholder="{{ translate('messages.your_first_name') }}"
                                                value="{{ auth('vendor')->check() ? auth('vendor')->user()->f_name : auth('vendor_employee')->user()->f_name }}">

                                            <input type="text" class="form-control" name="l_name"
                                                placeholder="{{ translate('messages.your_last_name') }}"
                                                value="{{ auth('vendor')->check() ? auth('vendor')->user()->l_name : auth('vendor_employee')->user()->l_name }}">
                                        </div>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label class="input-label">{{ translate('messages.phone') }}</label>

                                        <input type="text" class="js-masked-input form-control intl_input" name="phone"
                                            value="{{ auth('vendor')->check() ? auth('vendor')->user()->phone : auth('vendor_employee')->user()->phone }}"
                                            data-hs-mask-options='{"template": "+(880)00-000-00000"}'>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label class="input-label">{{ translate('messages.email') }}</label>

                                        <input type="email" class="form-control" name="email"
                                            value="{{ auth('vendor')->check() ? auth('vendor')->user()->email : auth('vendor_employee')->user()->email }}">
                                    </div>

                                </div>
                                <div class="col-md-3">
                                    <label for="">Image</label>
                                    <label
                                        class="avatar avatar-xxl avatar avatar-border-lg avatar-uploader profile-cover-avatar"
                                        for="customFileEg1">
                                        <img id="viewer"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            class="avatar-img onerror-image"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                auth('vendor')->check() ? auth('vendor')->user()->image : auth('vendor_employee')->user()->image,
                                                asset('storage/app/public/vendor/') .
                                                    '/' .
                                                    (auth('vendor')->check() ? auth('vendor')->user()->image : auth('vendor_employee')->user()->image),
                                                asset('public/assets/admin/img/160x160/img1.jpg'),
                                                'vendor/',
                                            ) }}"
                                            alt="Image">

                                        <input type="file" style="display:none" name="image"
                                            class="js-file-attach avatar-uploader-input" id="customFileEg1"
                                            accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">

                                        <label class="avatar-uploader-trigger" for="customFileEg1">
                                            <i class="tio-edit avatar-uploader-icon shadow-soft"></i>
                                        </label>
                                    </label>
                                </div>


                                <div class="d-flex justify-content-end mx-3 w-100">
                                    <button type="button" data-id="vendor-settings-form"
                                        data-message="{{ translate('you_want_to_update_user_info') }}"
                                        class="btn btn-primary {{ env('APP_MODE') != 'demo' ? 'form-alert' : 'call-demo' }}">
                                        {{ translate('messages.Save_changes') }}
                                    </button>
                                </div>

                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>

    </form>


    {{-- =========================
    PASSWORD MODAL
========================= --}}
    <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('messages.change_your_password') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body p-0">

                    <div class="card mb-0 profile-section-card">


                        <div class="card-body">

                            <form id="changePasswordForm"
                                action="{{ env('APP_MODE') != 'demo' ? route('vendor.profile.settings-password') : 'javascript:' }}"
                                method="post" enctype="multipart/form-data" class="row align-items-end">
                                @csrf

                                <div class="col-md-4 form-group mb-0">
                                    <label class="input-label">
                                        {{ translate('messages.new_password') }}
                                    </label>

                                    <input type="password" class="js-pwstrength form-control" name="password"
                                        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" required
                                        data-hs-pwstrength-options='{
                                            "ui": {
                                              "container": "#changePasswordForm",
                                              "viewports": {
                                                "progress": "#passwordStrengthProgress",
                                                "verdict": "#passwordStrengthVerdict"
                                              }
                                            }
                                       }'>

                                    <p id="passwordStrengthVerdict" class="form-text mb-0"></p>
                                    <div id="passwordStrengthProgress"></div>
                                </div>

                                <div class="col-md-4 form-group mb-0">
                                    <label class="input-label">{{ translate('messages.confirm_password') }}</label>

                                    <input type="password" class="form-control" name="confirm_password"
                                        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" required>
                                </div>

                                <div class="col-md-4 form-group mb-0 pt-2 border rounded">
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
                                    </label>
                                </div>

                                <div class="d-flex justify-content-end mx-3 w-100 mt-2">
                                    <button type="button" data-id="changePasswordForm"
                                        data-message="{{ translate('messages.want_to_update_password') }}"
                                        class="btn btn-primary {{ env('APP_MODE') != 'demo' ? 'form-alert' : 'call-demo' }}">
                                        {{ translate('messages.Save_changes') }}
                                    </button>
                                </div>

                            </form>

                            @if (auth('vendor_employee')->check())
                                @if (!$data['resign'])
                                    <button class="btn btn-danger mt-3" type="button" data-toggle="modal"
                                        data-target="#exampleModal">
                                        <i class="tio-remove-circle"></i>
                                        {{ translate('messages.Resign') }}
                                    </button>

                                    <div class="modal fade" id="exampleModal" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">

                                                <div class="modal-header">
                                                    <h5 class="modal-title">Resignation</h5>
                                                    <button type="button" class="close" data-dismiss="modal">
                                                        <span>&times;</span>
                                                    </button>
                                                </div>

                                                <div class="modal-body">
                                                    <form action="{{ route('vendor.employee.resign') }}" method="POST">
                                                        @csrf

                                                        <label>Reason</label>
                                                        <textarea name="reason" class="form-control" required></textarea>

                                                        <div class="d-flex justify-content-end w-100 mt-2">
                                                            <button class="btn btn-danger">Proceed</button>
                                                        </div>
                                                    </form>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <h4 class="text-danger mt-3">Resignation Submitted</h4>
                                @endif
                            @endif

                        </div>
                    </div>

                </div>

            </div>
        </div>

        {{-- ── Default Dashboard Preference (vendor only) ────── --}}
        @if(auth('vendor')->check())
        @php
            $storeConfig   = \App\Models\StoreConfig::where('store_id', \App\CentralLogics\Helpers::get_store_id())->first();
            $currentDash   = $storeConfig?->default_dashboard ?? 'main';
            $dashOptions   = [
                'main'           => ['label' => 'Main Dashboard',     'desc' => 'Default overview'],
                'leads_dashboard'=> ['label' => 'Leads Dashboard',    'desc' => 'Charts, stats & completion rates'],
                'leads'          => ['label' => 'Leads List',         'desc' => 'All service requests'],
                'hr'             => ['label' => 'HR Management',      'desc' => 'Staff, attendance & payroll'],
                'hospital'       => ['label' => 'Hospital Management','desc' => 'Appointments & OPD'],
            ];
        @endphp
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0" style="font-size:14px;font-weight:700;">
                    <i class="tio-home-vs-1-outlined mr-1"></i> Default Dashboard on Login
                </h5>
                <small class="text-muted" style="font-size:12px;">Choose which page opens when you log in or visit the panel.</small>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('vendor.profile.default-dashboard') }}">
                    @csrf
                    <div class="dashboard-options-row" style="display:flex;flex-wrap:wrap;gap:12px;">
                        @foreach($dashOptions as $key => $opt)
                        <div style="flex:1;min-width:160px;">
                            <label style="display:flex;align-items:flex-start;gap:10px;padding:14px 16px;border-radius:10px;border:2px solid {{ $currentDash === $key ? '#18181b' : '#e4e4e7' }};background:{{ $currentDash === $key ? '#18181b' : '#fff' }};cursor:pointer;transition:all .15s;">
                                <input type="radio" name="default_dashboard" value="{{ $key }}"
                                    {{ $currentDash === $key ? 'checked' : '' }}
                                    style="margin-top:2px;accent-color:#fff;flex-shrink:0;"
                                    onchange="this.form.submit()">
                                <div>
                                    <div style="font-size:13px;font-weight:700;color:{{ $currentDash === $key ? '#fff' : '#18181b' }};line-height:1.3;">
                                        {{ $opt['label'] }}
                                    </div>
                                    <div style="font-size:11px;color:{{ $currentDash === $key ? 'rgba(255,255,255,0.65)' : '#71717a' }};margin-top:2px;">{{ $opt['desc'] }}</div>
                                </div>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </form>
            </div>
        </div>
        @endif

    </div>
    <div class="modal fade" id="aboutStoreModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="{{ route('vendor.profile.about.update') }}">
                @csrf

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit About Section</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">

                        <div class="form-group">
                            <label>Why We Started</label>
                            <textarea class="form-control" name="why_we_started" rows="4">{{ $store->vendor?->why_we_started }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Our Idea</label>
                            <textarea class="form-control" name="our_idea" rows="4">{{ $store->vendor?->our_idea }}</textarea>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-primary">
                            Save
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

@endsection


@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/profile-index.js"></script>
    @include('admin-views.partials.tel_input')

    <script>
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

    @if (request('flag') && request('flag') == 'success')
        <script>
            $(document).ready(function() {
                toastr.success('Plan purchased successfully!', 'Success');

                const url = new URL(window.location);
                url.searchParams.delete('flag');
                url.searchParams.delete('token');
                window.history.replaceState({}, '', url);
            });
        </script>
    @endif
@endpush
