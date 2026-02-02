@extends('layouts.vendor.app')
@section('title', translate('messages.Profile'))
@push('css_or_js')
    <!-- Custom styles for this page -->
    <link href="{{ asset('public/assets/back-end') }}/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {

            --text-primary: #000000;
            --text-secondary: #2c2c2c;
            --bg-white: #FFFFFF;
            --bg-light: #F8F9FA;
            --border-color: #E0E0E0;
        }

        .profile-card {
            background: var(--bg-white);
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 24px;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 30px;
        }

        .profile-info {
            flex: 1;
        }

        .profile-label {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .profile-name {
            font-size: 32px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .profile-role {
            color: var(--primary);
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .profile-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .profile-detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .profile-detail-item i {
            color: var(--text-secondary);
            font-size: 16px;
            width: 20px;
        }

        .profile-detail-item a {
            color: var(--text-primary);
            text-decoration: none;
        }

        .profile-detail-item a:hover {
            color: var(--primary);
        }

        .profile-password {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .password-dots {
            letter-spacing: 2px;
        }

        .change-password-link {
            color: var(--primary);
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
        }

        .change-password-link:hover {
            text-decoration: underline;
        }

        .profile-meta {
            display: flex;
            gap: 24px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .meta-item i {
            color: var(--text-secondary);
        }

        .profile-avatar-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .profile-avatar-wrapper {
            position: relative;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #E8E8E8 0%, #D0D0D0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .profile-avatar-icon {
            font-size: 60px;
            color: var(--text-primary);
        }

        .camera-icon {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 42px;
            height: 42px;
            background-color: #4A4A4A;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .camera-icon i {
            color: white;
            font-size: 20px;
        }

        .avatar-name {
            color: var(--primary);
            font-size: 18px;
            font-weight: 600;
        }

        .avatar-role {
            color: var(--text-secondary);
            font-size: 14px;
        }

        /* Tabs Styling */
        .custom-tabs-container {
            background: var(--bg-white);
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .custom-nav-tabs {
            display: flex;
            background-color: var(--bg-light);
            border-bottom: 2px solid var(--border-color);
            padding: 0;
            margin: 0;
            list-style: none;
            overflow-x: auto;
        }

        .custom-nav-tabs::-webkit-scrollbar {
            height: 4px;
        }

        .custom-nav-tabs::-webkit-scrollbar-track {
            background: var(--bg-light);
        }

        .custom-nav-tabs::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 2px;
        }

        .custom-nav-item {
            flex-shrink: 0;
        }

        .custom-nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 16px 24px;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .custom-nav-link:hover {
            color: var(--primary);
            background-color: rgba(139, 75, 126, 0.05);
        }

        .custom-nav-link.active-tab {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background-color: var(--bg-white);
        }

        .custom-tab-content {
            padding: 30px;
        }

        .custom-tab-pane {
            display: none;
        }

        .custom-tab-pane.active-pane {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Content Cards */
        .content-card {
            background: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }

        .content-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-light-theme);
        }

        .card-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .card-subtitle {
            font-size: 14px;
            color: var(--primary);
            font-weight: 500;
        }

        .card-date {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .card-description {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-top: 12px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            background: var(--bg-light);
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .info-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .info-value {
            font-size: 16px;
            color: var(--text-primary);
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 48px;
            color: var(--border-color);
            margin-bottom: 16px;
        }

        .empty-state-text {
            font-size: 16px;
        }

        .badge-custom {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            background-color: rgba(139, 75, 126, 0.1);
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column-reverse;
                align-items: center;
                text-align: center;
            }

            .profile-meta {
                justify-content: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .profile-name {
                font-size: 24px;
            }
        }
    </style>

    {{-- documents  --}}
    <style>
        :root {
            --doc-text-primary: #080808;
            --doc-text-secondary: #292929;
            --doc-bg-white: #FFFFFF;
            --doc-bg-light: #F8F9FA;
            --doc-border-color: #E0E0E0;
            --doc-success-color: #10B981;
            --doc-info-color: #3B82F6;
        }

        .emp-documents-container {
            max-width: 100%;
            margin: 0 auto;
        }

        .emp-section-header {
            margin-bottom: 24px;
        }

        .emp-section-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--doc-text-primary);
            margin-bottom: 8px;
        }

        .emp-section-subtitle {
            color: var(--doc-text-secondary);
            font-size: 14px;
        }

        .emp-documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .emp-doc-card {
            background: var(--doc-bg-white);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid var(--doc-border-color);
            position: relative;
            overflow: hidden;
        }

        .emp-doc-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            border-color: var(--primary-light);
        }

        .emp-doc-card-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }

        .emp-doc-icon-wrapper {
            flex-shrink: 0;
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .emp-doc-icon-wrapper.emp-pdf-icon {
            background: linear-gradient(135deg, #f62121 0%, #ff4b4b 100%);
            ;
        }

        .emp-doc-icon-wrapper.emp-image-icon {
            background: linear-gradient(135deg, #0fd080 0%, #0de39f 100%);
        }

        .emp-doc-icon-wrapper.emp-default-icon {
            background: linear-gradient(135deg, var(--primary-clr) 0%, var(--primary) 100%);
        }

        .emp-doc-icon-wrapper i {
            font-size: 28px;
            color: white;
        }

        .emp-doc-card-content {
            flex: 1;
        }

        .emp-doc-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--doc-text-primary);
            margin-bottom: 6px;
            line-height: 1.3;
        }

        .emp-doc-number {
            font-size: 14px;
            color: var(--doc-text-secondary);
            background: var(--doc-bg-light);
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 4px;
        }

        .emp-doc-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--doc-border-color);
        }

        .emp-doc-label {
            font-size: 12px;
            color: var(--doc-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .emp-view-doc-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .emp-view-doc-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateX(2px);
            text-decoration: none;
        }

        .emp-view-doc-btn i {
            font-size: 14px;
        }


        .emp-empty-state {
            text-align: center;
            padding: 80px 20px;
            background: var(--doc-bg-white);
            border-radius: 12px;
            border: 2px dashed var(--doc-border-color);
        }

        .emp-empty-state i {
            font-size: 64px;
            color: var(--doc-border-color);
            margin-bottom: 20px;
        }

        .emp-empty-state-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--doc-text-primary);
            margin-bottom: 8px;
        }

        .emp-empty-state-text {
            color: var(--doc-text-secondary);
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .emp-documents-grid {
                grid-template-columns: 1fr;
            }

            .emp-section-title {
                font-size: 20px;
            }
        }

        @keyframes empFadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .emp-doc-card {
            animation: empFadeInUp 0.5s ease backwards;
        }

        .emp-doc-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .emp-doc-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .emp-doc-card:nth-child(3) {
            animation-delay: 0.3s;
        }

        .emp-doc-card:nth-child(4) {
            animation-delay: 0.4s;
        }

        .emp-doc-card:nth-child(5) {
            animation-delay: 0.5s;
        }

        .emp-doc-card:nth-child(6) {
            animation-delay: 0.6s;
        }
    </style>
@endpush

@section('content')

    <div class="content container-fluid">

        <div class="profile-container">
            <!-- Profile Card -->

            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-info">
                        <div class="profile-label">Full name</div>
                        <h1 class="profile-name">{{ $employee->f_name . ' ' . $employee->l_name }}</h1>
                        <div class="profile-role">{{ ucwords($employee->role?->name) }}</div>

                        <div class="profile-details">
                            <div class="profile-detail-item">
                                <i class="bi bi-telephone-fill"></i>
                                <a href="tel:+917022862888">{{ $employee->phone }}</a>
                            </div>
                            <div class="profile-detail-item">
                                <i class="bi bi-envelope-fill"></i>
                                <a href="mailto:pjspowerservicescenter@gmail.com">{{ $employee->email }}</a>
                            </div>
                            <div class="profile-detail-item">
                                <i class="bi bi-lock-fill"></i>
                                <div class="profile-password">
                                    <span>Password: <span class="password-dots">••••••••</span></span>
                                    <a href="#" class="change-password-link">Change Password</a>
                                </div>
                            </div>
                        </div>

                        <div class="profile-meta">
                            <div class="meta-item">
                                <i class="bi bi-calendar3"></i>
                                <span>Joined in :
                                    <strong>{{ $employee->tentative_joining_date ? $employee->tentative_joining_date : $employee->created_at->format('M Y') }}</strong></span>
                            </div>
                            <div class="meta-item">
                                <i class="bi bi-briefcase-fill"></i>
                                <span>Years of Experience : <strong>{{ $employee->experience_yrs }}</strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="profile-avatar-section">
                        {{-- <div class="profile-avatar-wrapper">
                            <div class="profile-avatar">
                                <i class="bi bi-person-fill profile-avatar-icon"></i>
                            </div>
                            <div class="camera-icon">
                                <i class="bi bi-camera-fill"></i>
                            </div>
                        </div> --}}
                        <label class="profile-avatar-wrapper"
                            style="    border: 1px solid #dbdbdb;width: 240px;height: 240px;">
                            <img style="width: 100%; height: 100% ; object-fit: cover;"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                class="avatar-img onerror-image"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper(auth('vendor')->check() ? auth('vendor')->user()->image : auth('vendor_employee')->user()->image, asset('storage/app/public/vendor/') . '/' . (auth('vendor')->check() ? auth('vendor')->user()->image : auth('vendor_employee')->user()->image), asset('public/assets/admin/img/160x160/img1.jpg'), 'vendor/') }}"
                                alt="Image">

                        </label>
                    </div>
                </div>
                <div class="d-flex w-100 justify-content-end mt-4">
                    <button type="button" data-toggle="modal" data-target="#profileEditModal"
                        class="btn btn-outline-primary">Edit Profile</button>
                </div>
                <div class="modal fade" id="profileEditModal" tabindex="-1" role="dialog"
                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('vendor.profile.update') }}" method="POST">
                                @csrf
                                    <div class="row">
                                        <div class="row col-md-8">
                                            <div class="form-group mb-0 col-md-6">
                                                <label for="">First Name</label>
                                                <input type="text" value={{$employee->f_name}} name="f_name" class="form-control">
                                            </div>
                                            <div class="form-group mb-0 col-md-6">
                                                <label for="">Last Name</label>
                                                <input type="text" value={{$employee->l_name}} name="l_name" class="form-control">
                                            </div>
                                            <div class="form-group mb-0 col-md-12">
                                                <label for="">Phone</label>
                                                <input type="number" value={{$employee->phone}} name="phone" class="form-control">
                                            </div>
                                            <div class="form-group mb-0 col-md-12">
                                                <label for="">Email</label>
                                                <input type="email" value={{$employee->email}} name="email" class="form-control">
                                            </div>


                                        </div>
                                        <div class="col-md-4">
                                            <label class="border avatar-border-lg avatar-uploader profile-cover-avatar"
                                                style="    border: 1px solid #dbdbdb;width: 140px;height: 140px;"
                                                for="avatarUploader">
                                                <img id="viewer"
                                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                    class="avatar-img onerror-image"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper(auth('vendor')->check() ? auth('vendor')->user()->image : auth('vendor_employee')->user()->image, asset('storage/app/public/vendor/') . '/' . (auth('vendor')->check() ? auth('vendor')->user()->image : auth('vendor_employee')->user()->image), asset('public/assets/admin/img/160x160/img1.jpg'), 'vendor/') }}"
                                                    alt="Image">
                                                <input type="file" name="image"
                                                    class="js-file-attach avatar-uploader-input" id="customFileEg1"
                                                    accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                <label class="avatar-uploader-trigger position-relative"
                                                    for="customFileEg1">
                                                    <div class="camera-icon">
                                                        <i class="bi bi-camera-fill"></i>
                                                    </div>
                                                </label>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end mt-4">
                                        <button class="btn-primary btn">Update</button>
                                    </div>

                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Section -->
            <div class="custom-tabs-container">
                <ul class="custom-nav-tabs" role="tablist">
                    <li class="custom-nav-item">
                        <a class="custom-nav-link active-tab" data-tab="contact" href="#contact">
                            <i class="bi bi-briefcase"></i>
                            Contact Info
                        </a>
                    </li>
                    <li class="custom-nav-item">
                        <a class="custom-nav-link" data-tab="experience" href="#experience">
                            <i class="bi bi-briefcase"></i>
                            Experience
                        </a>
                    </li>
                    <li class="custom-nav-item">
                        <a class="custom-nav-link" data-tab="education" href="#education">
                            <i class="bi bi-mortarboard"></i>
                            Education
                        </a>
                    </li>
                    <li class="custom-nav-item">
                        <a class="custom-nav-link" data-tab="salary" href="#salary">
                            <i class="bi bi-currency-dollar"></i>
                            Salary Info
                        </a>
                    </li>
                    <li class="custom-nav-item">
                        <a class="custom-nav-link" data-tab="documents" href="#documents">
                            <i class="bi bi-building"></i>
                            Documents
                        </a>
                    </li>
                    <li class="custom-nav-item">
                        <a class="custom-nav-link" data-tab="role" href="#role">
                            <i class="bi bi-person-badge"></i>
                            Role
                        </a>
                    </li>
                </ul>

                <div class="custom-tab-content">
                    <!-- Address Tab -->
                    @php
                        $ra = json_decode($employee->residential_address);
                        $pa = json_decode($employee->permanent_address);
                        $experiences = json_decode($employee->experience) ?? [];
                        $education = json_decode($employee->education) ?? [];
                    @endphp
                    <div class="custom-tab-pane active-pane" id="contact">
                        <div class="content-card">
                            <div class="card-header-custom">
                                <div>
                                    <h3 class="card-title">Residential Address</h3>
                                    <div class="card-subtitle">{{ $ra?->ra_phone }}</div>
                                </div>
                                <div class="card-date">-</div>
                            </div>
                            <div class="card-description">
                                {{ $ra?->ra_address1 }}, {{ $ra?->ra_address2 }}, {{ $ra?->ra_city }} -
                                {{ $ra?->ra_pincode }}.
                            </div>
                            <div style="margin-top: 12px;">
                                <span class="badge-custom">{{ $ra?->ra_email }}</span>
                            </div>
                        </div>


                        <div class="content-card">
                            <div class="card-header-custom">
                                <div>
                                    <h3 class="card-title">Permanent Address</h3>
                                    <div class="card-subtitle">{{ $pa?->pa_phone }}</div>
                                </div>
                                <div class="card-date">-</div>
                            </div>
                            <div class="card-description">
                                {{ $pa?->pa_address1 }}, {{ $pa?->pa_address2 }}, {{ $pa?->pa_city }} -
                                {{ $pa?->pa_pincode }}.
                            </div>
                            <div style="margin-top: 12px;">
                                <span class="badge-custom">{{ $pa?->pa_email }}</span>
                            </div>
                        </div>
                        {{-- @foreach ($employee->skills as $key => $value)
    
@endforeach --}}
                        <div style="margin-top: 12px;">
                            <span class="badge-custom">{{ $pa?->pa_email }}</span>
                        </div>


                    </div>
                    <!-- Experience Tab -->
                    <div class="custom-tab-pane" id="experience">
                        @foreach ($experiences as $key => $experience)
                            <div class="content-card">
                                <div class="card-header-custom">
                                    <div>
                                        <h3 class="card-title">{{ $experience?->occupation }}</h3>
                                        <div class="card-subtitle">{{ $experience?->company_name }}</div>
                                    </div>
                                    <div class="card-date">
                                        {{ $experience?->exp_start_date . ($experience?->currently_working ? ' -present' : $experience?->exp_end_date) }}
                                    </div>
                                </div>
                                <div class="card-description">
                                    {{ $experience?->summary }}
                                </div>
                                <div style="margin-top: 12px;">
                                    <span class="badge-custom">{{ $experience?->summary }}</span>
                                    <span class="badge-custom"><a
                                            href="">{{ $experience?->exp_letter }}</a></span>
                                </div>
                            </div>
                        @endforeach

                        @if ($employee->skills)

                            <div class="content-card">
                                <h3 class="card-title" style="margin-bottom: 16px;">Key Skills & Technologies</h3>
                                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                    @foreach (explode(',', $employee->skills) as $key => $value)
                                        <span class="badge-custom">{{ $value }}</span>
                                    @endforeach

                                </div>
                            </div>

                        @endif

                    </div>

                    <!-- Education Tab -->
                    <div class="custom-tab-pane" id="education">
                        @foreach ($education as $key => $ed)
                            <div class="content-card">
                                <div class="card-header-custom">
                                    <div>
                                        <h3 class="card-title">{{ $ed?->degree_diploma }} @if ($ed?->field_of_study)
                                                ({{ $ed?->field_of_study }})
                                            @endif
                                        </h3>
                                        <div class="card-subtitle">{{ $ed?->school_name }}</div>
                                    </div>
                                    <div class="card-date">
                                        {{ $ed?->start_month . ' - ' . $ed?->end_month }}
                                    </div>
                                    <div class="card-description">
                                        {{ $ed?->additional_notes }}
                                    </div>
                                    <div style="margin-top: 12px;">
                                        {{-- <span class="badge-custom">GPA: 3.8/4.0</span> --}}
                                    </div>
                                </div>
                            </div>
                        @endforeach


                    </div>

                    <!-- Salary Info Tab -->
                    <div class="custom-tab-pane" id="salary">
                        <div class="info-grid">

                            <div class="info-item">
                                <div class="info-label">Salary Frequency</div>
                                <div class="info-value">{{ $employee->salary_type }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Base Salary</div>
                                <div class="info-value">{{ _price($employee->base_salary) }}</div>
                            </div>

                        </div>


                    </div>



                    <!-- Role Tab -->
                    <div class="custom-tab-pane" id="role">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Department Name</div>
                                <div class="info-value">{{ $employee->department?->title }}</div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Job Title</div>
                                <div class="info-value">{{ $employee->designation }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Branch</div>
                                <div class="info-value">{{ $employee->branch?->name }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Employee ID</div>
                                <div class="info-value">{{ $employee->employee_id }}</div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Joining Date</div>
                                <div class="info-value">{{ $employee->tentative_joining_date ?? $employee->created_at }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="custom-tab-pane" id="documents">
                        @if ($employee->documentation && is_array(json_decode($employee->documentation)))
                            @php
                                $documents = json_decode($employee->documentation);
                            @endphp

                            @if (count($documents) > 0)
                                <div class="emp-documents-grid">
                                    @foreach ($documents as $index => $document)
                                        @php
                                            // Determine file extension for icon
                                            $fileExtension = $document->doc_file
                                                ? pathinfo($document->doc_file, PATHINFO_EXTENSION)
                                                : '';
                                            $iconClass = 'emp-default-icon';
                                            $iconName = 'bi-file-earmark-text';

                                            if (in_array(strtolower($fileExtension), ['pdf'])) {
                                                $iconClass = 'emp-pdf-icon';
                                                $iconName = 'bi-file-earmark-pdf';
                                            } elseif (
                                                in_array(strtolower($fileExtension), [
                                                    'jpg',
                                                    'jpeg',
                                                    'png',
                                                    'gif',
                                                    'webp',
                                                ])
                                            ) {
                                                $iconClass = 'emp-image-icon';
                                                $iconName = 'bi-file-earmark-image';
                                            }

                                        @endphp

                                        <div class="emp-doc-card">
                                            <div class="emp-doc-card-header">
                                                <div class="emp-doc-icon-wrapper {{ $iconClass }}">
                                                    <i class="bi {{ $iconName }}"></i>
                                                </div>
                                                <div class="emp-doc-card-content">
                                                    <div class="emp-doc-name">{{ $document->doc_name ?? 'Document' }}
                                                    </div>
                                                    @if (isset($document->doc_number) && $document->doc_number)
                                                        <div class="emp-doc-number">{{ $document->doc_number }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="emp-doc-card-footer">
                                                <span class="emp-doc-label">ID Document</span>
                                                @if (isset($document->doc_file) && $document->doc_file)
                                                    <a href="{{ asset('storage/app/public/vendor/documents/' . $document->doc_file) }}"
                                                        class="btn btn-outline-primary" target="_blank">
                                                        <i class="bi bi-eye"></i>
                                                        View Current
                                                    </a>
                                                @else
                                                    <span class="emp-doc-label" style="color: #999;">No file
                                                        uploaded</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="emp-empty-state">
                                    <i class="bi bi-file-earmark-x"></i>
                                    <div class="emp-empty-state-title">No Documents Found</div>
                                    <div class="emp-empty-state-text">There are no documents available for this employee.
                                    </div>
                                </div>
                            @endif
                        @else
                            <div class="emp-empty-state">
                                <i class="bi bi-file-earmark-x"></i>
                                <div class="emp-empty-state-title">No Documents Found</div>
                                <div class="emp-empty-state-text">There are no documents available for this employee.</div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection

@push('script')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/profile-index.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Tab switching functionality
            $('.custom-nav-link').on('click', function(e) {
                e.preventDefault();

                // Get the target tab
                const targetTab = $(this).data('tab');

                // Remove active class from all tabs and panes
                $('.custom-nav-link').removeClass('active-tab');
                $('.custom-tab-pane').removeClass('active-pane');

                // Add active class to clicked tab and corresponding pane
                $(this).addClass('active-tab');
                $('#' + targetTab).addClass('active-pane');
            });

            // Change password link handler
            $('.change-password-link').on('click', function(e) {
                e.preventDefault();
                {{-- alert('Change password functionality would be implemented here'); --}}
            });

            // Camera icon click handler
            $('.camera-icon').on('click', function() {
                {{-- alert('Upload photo functionality would be implemented here'); --}}
            });

            // Smooth scroll animation for tab content
            $('.custom-nav-link').on('click', function() {
                $('html, body').animate({
                    scrollTop: $('.custom-tabs-container').offset().top - 20
                }, 400);
            });
        });
    </script>
@endpush
