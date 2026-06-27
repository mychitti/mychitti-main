@extends('layouts.vendor.app')
@section('title', translate('messages.edit_store'))
@push('css_or_js')
    <!-- Custom styles for this page -->
    <link href="{{ asset('public/assets/admin') }}/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <!-- Custom styles for this page -->
    <link href="{{ asset('public/assets/admin/css/croppie.css') }}" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .vdp-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .vdp-heading {
            font-size: 24px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .vdp-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .vdp-doc-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .vdp-doc-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .vdp-card-header {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 16px;
        }

        .vdp-file-icon {
            width: 50px;
            height: 50px;
            background: #e4e4e4ff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .vdp-card-info {
            flex: 1;
        }

        .vdp-doc-filename {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        .vdp-doc-meta {
            font-size: 13px;
            color: #666;
        }

        .vdp-card-body {
            margin-bottom: 16px;
        }

        .vdp-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .vdp-info-row:last-child {
            border-bottom: none;
        }

        .vdp-info-label {
            font-size: 13px;
            color: #666;
        }

        .vdp-info-value {
            font-size: 13px;
            color: #1a1a1a;
            font-weight: 500;
        }

        .vdp-status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .vdp-status-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .vdp-status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .vdp-status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .vdp-card-footer {
            display: flex;
            gap: 8px;
        }


        .vdp-btn-primary {
            background: var(--primary, #4f46e5);
            color: white;
            border-color: var(--primary, #4f46e5);
        }

        .vdp-btn-primary:hover {
            background: var(--primary-light, #6366f1);
            border-color: var(--primary-light, #6366f1);
            color: white;
        }

        @media (max-width: 768px) {
            .vdp-cards-grid {
                grid-template-columns: 1fr;
            }
        }

        .vdp-empty-state {
            background: white;
            border-radius: 10px;
            padding: 60px 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .vdp-empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .vdp-empty-title {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .vdp-empty-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 24px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .vdp-empty-state .vdp-btn-upload {
            display: inline-block;
        }

        .form-row {
            margin-top: 6px;
            padding: 0 12px !important;

        }

        .form-label {
            font-size: 11px;
            font-weight: bold;
            line-height: 19px;
            margin-bottom: 0px !important;
        }

        .form-group {
            padding: 5px !important;
            margin-bottom: 0px !important;

        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 20px;
        }

        .profile-card {
            border: 1px solid #eee;
            border-radius: 14px;
            padding: 18px;
            background: #fff;
        }

        .section-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .upload-card {
            border: 1px dashed #dcdcdc;
            border-radius: 14px;
            padding: 12px;
            text-align: center;
            background: #fafafa;
        }

        .upload-preview {
            width: 100%;
            height: 160px;
            object-fit: contain;
            border-radius: 10px;
            background: #fff;
        }

        .cover-preview {
            height: 180px;
            object-fit: contain;
        }

        .map-card {
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #eee;
            position: relative;
        }

        #map {
            height: 420px;
            width: 100%;
        }

        .profile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .profile-user {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .profile-user img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }

        .small-label {
            font-size: 11px;
            font-weight: 600;
            color: #666;
            margin-bottom: 4px;
        }

        .form-control {
            border-radius: 10px;
        }

        .custom-file-label {
            border-radius: 10px;
        }

        @media(max-width:991px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush
@section('content')
    <!-- Content Row -->
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between">
            <h2 class="page-header-title text-capitalize">
                <img class="w--26" src="{{ asset('/public/assets/admin/img/store.png') }}" alt="public">
                <span>
                    {{ translate('messages.store settings') }}
                </span>
            </h2>
            <button type="button" class="btn btn-outline-primary" data-toggle="modal"
                data-target="#ammouncementModal" > <i class="tio-volume-up"></i> Announcement</button>
        </div>
        @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
        @php($language = $language->value ?? null)
        @php($defaultLang = 'en')
        <form action="{{ route('vendor.shop.update') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">

                            <div class="profile-grid">

                                {{-- LEFT PANEL --}}
                                <div class="profile-card">

                                    <div class="section-title">Business Profile</div>

                                    <div class="row">

                                        <div class="col-md-6 form-group">
                                            <label class="small-label">Legal Business Name</label>
                                            <input type="text" name="name[]" class="form-control"
                                                value="{{ $shop->getRawOriginal('name') }}" required>
                                        </div>

                                        <div class="col-md-6 form-group">
                                            <label class="small-label">Contact Number</label>
                                            <input type="text" name="contact" value="{{ $shop->phone }}"
                                                class="form-control intl_input" required>
                                        </div>

                                        <input type="hidden" name="lang[]" value="default">

                                        <div class="col-md-6 form-group">
                                            <label class="small-label">Business Address</label>
                                            <input type="text" name="address[]"
                                                value="{{ $shop->getRawOriginal('address') }}" class="form-control"
                                                required>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label class="small-label">Documents</label>
                                            <div class="gap-2 d-flex flex-wrap">
                                                <button type="button" class="btn btn-outline-primary" data-toggle="modal"
                                                    data-target="#gstDocUpdateModal">GST
                                                    Document</button>
                                                <button type="button" class="btn btn-outline-primary" data-toggle="modal"
                                                    data-target="#idDocUpdateModal">ID Proof
                                                    Document</button>
                                                <button type="button" class="btn btn-outline-primary" data-toggle="modal"
                                                    data-target="#fssaiDocUpdateModal">FSSAI Document</button>
                                                <button type="button" class="btn btn-outline-primary" data-toggle="modal"
                                                    data-target="#otherDocsModal">Other Documents</button>
                                            </div>
                                        </div>



                                    </div>

                                    <div class="section-title mt-3">Brand Images</div>

                                    <div class="row">

                                        {{-- Logo --}}
                                        <div class="col-md-6 mb-3">
                                            <div class="upload-card">
                                                <div class="small-label mb-2">Brand Logo (500×500)</div>

                                                <img id="viewer" class="upload-preview"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                        $shop->logo,
                                                        asset('storage/app/public/store/' . $shop->logo),
                                                        asset('public/assets/admin/img/image-place-holder.png'),
                                                        'store/',
                                                    ) }}">

                                                <div class="custom-file mt-2">
                                                    <input type="file" name="image" class="custom-file-input"
                                                        id="customFileUpload">
                                                    {{-- <label class="custom-file-label" for="customFileUpload">
                                Choose file
                            </label> --}}
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Cover --}}
                                        <div class="col-md-6 mb-3">
                                            <div class="upload-card">
                                                <div class="small-label mb-2">Cover Photo (1050×500)</div>

                                                <img id="coverImageViewer" class="upload-preview cover-preview"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                        $shop->cover_photo,
                                                        asset('storage/app/public/store/cover/' . $shop->cover_photo),
                                                        asset('public/assets/admin/img/restaurant_cover.jpg'),
                                                        'store/cover/',
                                                    ) }}">

                                                <div class="custom-file mt-2">
                                                    <input type="file" name="photo" id="coverImageUpload"
                                                        class="custom-file-input">
                                                    {{-- <label class="custom-file-label" for="coverImageUpload">
                                Choose file
                            </label> --}}
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <input type="hidden" name="latitude" id="latitude" value="{{ $shop->latitude }}">
                                    <input type="hidden" name="longitude" id="longitude" value="{{ $shop->longitude }}">

                                </div>


                                {{-- RIGHT PANEL --}}
                                <div>
                                <div class="profile-card">
                                    <div class="profile-header">

                                        <div class="col-md-12 form-group">
                                            <label class="small-label">Service Location</label>
                                            <select name="zone_id" id="choice_zones"
                                                class="form-control js-select2-custom get_zone_data">
                                                @foreach (\App\Models\Zone::active()->get() as $zone)
                                                    <option value="{{ $zone->id }}"
                                                        {{ $shop->zone_id == $zone->id ? 'selected' : '' }}>
                                                        {{ $zone->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                    </div>

                                    <div class="small-label mb-2">
                                        Select your business location
                                    </div>

                                    <input id="pac-input" class="form-control mb-2" type="text"
                                        placeholder="Search your location">

                                    <div class="map-card">
                                        <div id="map"></div>
                                    </div>
                                </div>

                                <div class="profile-card mt-2">
                                    <div class="form-group mb-0 p-1">
                                        <label class="input-label text-capitalize"
                                            for="minimum_delivery_time">{{ $store->module->id == 5 ? translate('messages.approx_delivery_time') : 'Response Time' }}<span
                                                class="input-label-secondary" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('Set_the_total_time_to_deliver_products.') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('Set_the_total_time_to_deliver_products.') }}"></span></label>
                                        <div class="input-group">
                                            <input type="number" id="minimum_delivery_time" name="minimum_delivery_time"
                                                class="form-control" placeholder="Min: 10"
                                                value="{{ explode('-', $store->delivery_time)[0] }}"
                                                title="{{ translate('messages.minimum_delivery_time') }}">
                                            <input type="number" name="maximum_delivery_time" class="form-control"
                                                placeholder="Max: 20"
                                                value="{{ explode(' ', explode('-', $store->delivery_time)[1])[0] }}"
                                                title="{{ translate('messages.maximum_delivery_time') }}">
                                            <select name="delivery_time_type" class="form-control text-capitalize"
                                                required>
                                                <option value="min"
                                                    {{ isset(explode(' ', explode('-', $store->delivery_time)[1])[1]) && explode(' ', explode('-', $store->delivery_time)[1])[1] == 'min' ? 'selected' : '' }}>
                                                    {{ translate('messages.minutes') }}</option>
                                                <option value="hours"
                                                    {{ isset(explode(' ', explode('-', $store->delivery_time)[1])[1]) && explode(' ', explode('-', $store->delivery_time)[1])[1] == 'hours' ? 'selected' : '' }}>
                                                    {{ translate('messages.hours') }}</option>
                                                <option value="days"
                                                    {{ isset(explode(' ', explode('-', $store->delivery_time)[1])[1]) && explode(' ', explode('-', $store->delivery_time)[1])[1] == 'days' ? 'selected' : '' }}>
                                                    {{ translate('messages.days') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class=" p-1">
                                        <div class="form-group mb-0 ">
                                            <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                                for="gst_status">
                                                <span>{{ translate('messages.gst') }} <span class="form-label-secondary"
                                                        data-toggle="tooltip" data-placement="right"
                                                        data-original-title="{{ translate('messages.gst_status_warning') }}"><img
                                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                            alt="{{ translate('messages.gst_status_warning') }}"></span></span>
                                                <input type="checkbox" class="toggle-switch-input" name="gst_status"
                                                    id="gst_status" value="1"
                                                    {{ $store->gst_status ? 'checked' : '' }}>
                                                <span class="toggle-switch-label">
                                                    <span class="toggle-switch-indicator"></span>
                                                </span>
                                            </label>
                                            <input type="text" id="gst" name="gst" class="form-control"
                                                value="{{ $store->gst_code }}"
                                                {{ isset($store->gst_status) ? '' : 'readonly' }}>
                                        </div>
                                    </div>
                                    <div class=" p-1">
                                        <div class="form-group mb-0 ">
                                            <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                                for="fssai_show_inline">
                                                <span>FSSAI <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="Show FSSAI number on bills / labels"><img
                                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                            alt="FSSAI"></span></span>
                                                <input type="checkbox" class="toggle-switch-input" name="fssai_show"
                                                    id="fssai_show_inline" value="1"
                                                    {{ ($store->fssai_show ?? false) ? 'checked' : '' }}>
                                                <span class="toggle-switch-label">
                                                    <span class="toggle-switch-indicator"></span>
                                                </span>
                                            </label>
                                            <input type="text" id="fssai_number" name="fssai_number" class="form-control"
                                                value="{{ $store->fssai_number ?? '' }}" placeholder="FSSAI Number">
                                        </div>
                                    </div>

                                </div>
                                </div>

                            </div>

                        </div>

                    </div>
                </div>
            </div>

    </div>
    <div class="mt-3 justify-content-end btn--container">
        <a class="btn btn--danger text-capitalize"
            href="{{ route('vendor.shop.view') }}">{{ translate('messages.cancel') }}</a>
        <button type="submit" class="btn btn--primary text-capitalize"
            id="btn_update">{{ translate('messages.update') }}</button>
    </div>
    </form>

    </div>
    <div class="modal fade" id="ammouncementModal" tabindex  ="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Store Announcement</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="profile-card">

                        <div class="card-body p-0 ">
                            {{-- <div class="d-flex  justify-content-between border p-2 mx-2">
                                <h4 class="card-title align-items-center d-flex">
                                    <img src="{{ asset('public/assets/admin/img/store.png') }}" class="w--20 mr-1"
                                        alt="">
                                    <span>{{ translate('messages.store_temporarily_closed') }}</span>
                                </h4>
                                <label class="switch toggle-switch-lg m-0">
                                    <input type="checkbox" class="toggle-switch-input restaurant-open-status"
                                        {{ $store->active ? '' : 'checked' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </div> --}}
                            <div class="card-header">
                                <h5 class="card-title toggle-switch toggle-switch-sm d-flex justify-content-between">
                                    <span class="card-header-icon mr-1"><i class="tio-dashboard"></i></span>
                                    <span>{{ translate('Announcement') }}</span><span class="input-label-secondary"
                                        data-toggle="tooltip" data-placement="right"
                                        data-original-title="{{ translate('This_feature_is_for_sharing_important_information_or_announcements_related_to_the_store.') }}"><img
                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                            alt="{{ translate('messages.This_feature_is_for_sharing_important_information_or_announcements_related_to_the_store') }}"></span>
                                </h5>
                                <label class="toggle-switch toggle-switch-sm" for="announcement_status">
                                    <input class="toggle-switch-input dynamic-checkbox" type="checkbox"
                                        id="announcement_status" data-id="announcement_status" data-type="status"
                                        data-image-on='{{ asset('/public/assets/admin/img/modal') }}/digital-payment-on.png'
                                        data-image-off="{{ asset('/public/assets/admin/img/modal') }}/digital-payment-off.png"
                                        data-title-on="{{ translate('Do_you_want_to_enable_the_announcement') }}"
                                        data-title-off="{{ translate('Do_you_want_to_disable_the_announcement') }}"
                                        data-text-on="<p>{{ translate('User_will_able_to_see_the_Announcement_on_the_store_page.') }}</p>"
                                        data-text-off="<p>{{ translate('User_will_not_be_able_to_see_the_Announcement_on_the_store_page') }}</p>"
                                        name="announcement" value="1" {{ $shop->announcement ? 'checked' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </div>
                            <form
                                action="{{ route('vendor.business-settings.toggle-settings', [$shop->id, $shop->announcement ? 0 : 1, 'announcement']) }}"
                                method="get" id="announcement_status_form">
                            </form>
                            <div class="card-body p-1">
                                <form action="{{ route('vendor.shop.update-message') }}" method="post">
                                    @csrf
                                    <textarea name="announcement_message" id="" class="form-control" rows="5"
                                        placeholder="{{ translate('messages.ex_:_ABC_Company') }}">{{ $shop->announcement_message ?? '' }}</textarea>
                                    <div class="justify-content-end btn--container mt-2">
                                        <button type="submit"
                                            class="btn btn--primary">{{ translate('publish') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>


                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="gstDocUpdateModal" tabindex  ="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">GST Document</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <!-- Document Card 1 -->
                    <div class="vdp-doc-card"> 
                        <div class="vdp-card-header">
                            <div class="vdp-file-icon">
                                {{ $gstFilePath ? _getFileTypeLabel($gstFilePath) : 'GST' }}
                            </div>
                            <div class="vdp-card-info">
                                <div class="vdp-doc-filename">GST Document</div>
                            </div>
                        </div>
                        <div class="vdp-card-body">
                            <div class="vdp-info-row">
                                <span class="vdp-info-label">GST Number</span>
                                <span class="vdp-info-value">{{ $store->gst_number ?? '-' }}</span>
                            </div>
                            <div class="vdp-info-row">
                                <span class="vdp-info-label">File Type</span>
                                <span class="vdp-info-value">{{ $gstFilePath ? _getFileTypeLabel($gstFilePath) : '-' }}</span>
                            </div>
                            <div class="vdp-info-row">
                                <span class="vdp-info-label">Status</span>
                                @if ($gst_doc)
                                    @if ($gst_doc->verified == 0)
                                        <span class="vdp-status-badge vdp-status-pending">Pending</span>
                                    @else
                                        <span class="vdp-status-badge vdp-status-approved">Approved</span>
                                    @endif
                                @elseif ($store->gst_doc)
                                    <span class="vdp-status-badge vdp-status-approved">Approved</span>
                                @else
                                    <span class="text-muted">No document uploaded</span>
                                @endif
                            </div>
                        </div>

                        <div class="vdp-card-footer align-items-start flex-wrap">
                            @if ($gstFilePath)
                                <a href="{{ asset('storage/app/public/store/docs') . '/' . $gstFilePath }}" class="btn btn-primary">View</a>
                                <a download href="{{ asset('storage/app/public/store/docs') . '/' . $gstFilePath }}" class="btn btn-outline-primary">Download</a>
                            @endif

                            <button class="btn btn-outline-primary" type="button" data-toggle="collapse"
                                data-target="#collapseExample" aria-expanded="false"
                                aria-controls="collapseExample">Update</button>
                            <div class="collapse w-100" id="collapseExample">
                                <div class="card card-body">
                                    <form method="POST" enctype="multipart/form-data"
                                        action="{{ route('vendor.business-settings.update-doc') }}">
                                        @csrf
                                        <input type="hidden" name="file_type" value="gst_doc">
                                        <div class="form-group">
                                            <label for="gst_number">GST Number</label>
                                            <input type="text" class="form-control" id="gst_number" name="gst_number"
                                                value="{{ $store->gst_number ?? '' }}" placeholder="Enter GST Number">
                                        </div>
                                        <div class="form-group">
                                            <label for="gst_doc">Upload New GST Document</label>
                                            <input type="file" class="form-control" id="gst_doc" name="gst_doc">
                                        </div>
                                        <div class="d-flex w-100 justify-content-end">

                                            <button type="submit" class="btn btn-primary">Update</button>
                                        </div>
                                    </form>

                                </div>
                            </div>


                        </div>
                    </div>


                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="idDocUpdateModal" tabindex  ="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">ID Proof</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <!-- Document Card 1 -->
                    <div class="vdp-doc-card">
                        <div class="vdp-card-header">
                            <div class="vdp-file-icon">
                                {{ $idFilePath ? _getFileTypeLabel($idFilePath) : 'ID' }}</div>
                            <div class="vdp-card-info">
                                <div class="vdp-doc-filename">ID Proof</div>
                            </div>
                        </div> 
                        <div class="vdp-card-body">
                            <div class="vdp-info-row">
                                <span class="vdp-info-label">ID Number</span>
                                <span class="vdp-info-value">{{ $store->id_number ?? '-' }}</span>
                            </div>
                            <div class="vdp-info-row">
                                <span class="vdp-info-label">File Type</span>
                                <span class="vdp-info-value">{{ $idFilePath ? _getFileTypeLabel($idFilePath) : '-' }}</span>
                            </div>
                            <div class="vdp-info-row">
                                <span class="vdp-info-label">Status</span>
                                <div class="gap-1 d-flex">
                                    @if ($id_doc)
                                        @if ($id_doc->verified == 0)
                                            <b>Front : </b>
                                            <span class="vdp-status-badge vdp-status-pending">Pending</span>
                                        @else
                                            <span class="vdp-status-badge vdp-status-approved">Approved</span>
                                        @endif
                                    @elseif ($store->id_doc)
                                        <span class="vdp-status-badge vdp-status-approved">Approved</span>
                                    @else
                                        <span class="text-muted">No document uploaded</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="vdp-card-footer align-items-start flex-wrap">
                            @if ($idFilePath)
                                <a href="{{ asset('storage/app/public/store/docs') . '/' . $idFilePath }}" class="btn btn-primary">View Front</a>
                                @if ($id_doc && $id_doc->back_side)
                                    <a href="{{ asset('storage/app/public/store/docs') . '/' . $id_doc->back_side }}" class="btn btn-primary">View Back</a>
                                @endif
                                <a download href="{{ asset('storage/app/public/store/docs') . '/' . $idFilePath }}" class="btn btn-outline-primary">Download Front</a>
                                @if ($id_doc && $id_doc->back_side)
                                    <a download href="{{ asset('storage/app/public/store/docs') . '/' . $id_doc->back_side }}" class="btn btn-outline-primary">Download Back</a>
                                @endif
                            @endif

                            <button class="btn btn-outline-primary" type="button" data-toggle="collapse"
                                data-target="#collapseExample" aria-expanded="false"
                                aria-controls="collapseExample">Update</button>
                            <div class="collapse w-100" id="collapseExample">
                                <div class="card card-body">
                                    <form method="POST" enctype="multipart/form-data"
                                        action="{{ route('vendor.business-settings.update-doc') }}">
                                        @csrf
                                        <input type="hidden" name="file_type" value="id_doc">
                                        <div class="form-group">
                                            <label for="id_number">ID Number</label>
                                            <input type="text" class="form-control" id="id_number" name="id_number"
                                                value="{{ $store->id_number ?? '' }}" placeholder="Enter ID Number">
                                        </div>
                                        <div class="form-group">
                                            <label for="id_doc">New ID Proof (Front / both sides)</label>
                                            <input type="file" class="form-control" id="id_doc" name="id_doc">
                                        </div>
                                        <div class="form-group">
                                            <label for="id_doc_back">New ID Proof (Back side)</label>
                                            <input type="file" class="form-control" id="id_doc_back" 
                                                name="id_doc_back">
                                        </div>
                                        <div class="d-flex w-100 justify-content-end">
                                            <button type="submit" class="btn btn-primary">Update</button>
                                        </div>
                                    </form>

                                </div>
                            </div>

                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ FSSAI Document (number + file, same as GST) ============ --}}
    <div class="modal fade" id="fssaiDocUpdateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">FSSAI Document</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="vdp-doc-card">
                        <div class="vdp-card-header">
                            <div class="vdp-file-icon">{{ $fssai_doc ? _getFileTypeLabel($fssai_doc->file_path) : 'FSSAI' }}
                            </div>
                            <div class="vdp-card-info">
                                <div class="vdp-doc-filename">FSSAI Document</div>
                            </div>
                        </div>
                        <div class="vdp-card-body">
                            <div class="vdp-info-row"><span class="vdp-info-label">FSSAI Number</span><span
                                    class="vdp-info-value">{{ $store->fssai_number ?? '—' }}</span></div>
                            <div class="vdp-info-row">
                                <span class="vdp-info-label">Show on Bills / Labels</span>
                                <form method="POST" action="{{ route('vendor.business-settings.update-doc') }}"
                                    id="fssaiShowForm" class="m-0">
                                    @csrf
                                    <input type="hidden" name="file_type" value="fssai_doc">
                                    <input type="hidden" name="fssai_show_present" value="1">
                                    <label class="switch toggle-switch-sm m-0" for="fssai_show_toggle">
                                        <input type="checkbox" class="toggle-switch-input" name="fssai_show"
                                            id="fssai_show_toggle" value="1"
                                            {{ ($store->fssai_show ?? false) ? 'checked' : '' }}
                                            onchange="document.getElementById('fssaiShowForm').submit()">
                                        <span class="toggle-switch-label"><span
                                                class="toggle-switch-indicator"></span></span>
                                    </label>
                                </form>
                            </div>
                            <div class="vdp-info-row"><span class="vdp-info-label">File Type</span><span
                                    class="vdp-info-value">{{ $fssai_doc ? _getFileTypeLabel($fssai_doc->file_path) : '' }}</span>
                            </div>
                            <div class="vdp-info-row">
                                <span class="vdp-info-label">Status</span>
                                @if ($fssai_doc)
                                    @if ($fssai_doc->verified == 0)
                                        <span class="vdp-status-badge vdp-status-pending">Pending</span>
                                    @else
                                        <span class="vdp-status-badge vdp-status-approved">Approved</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="vdp-card-footer align-items-start flex-wrap">
                            @if ($fssai_doc)
                                <a href="{{ asset('storage/app/public/store/docs') . '/' . $fssai_doc->file_path }}"
                                    class="btn btn-primary">View</a>
                                @if ($fssai_doc->back_side)
                                    <a href="{{ asset('storage/app/public/store/docs') . '/' . $fssai_doc->back_side }}"
                                        class="btn btn-primary">View Back</a>
                                @endif
                                <a download href="{{ asset('storage/app/public/store/docs') . '/' . $fssai_doc->file_path }}"
                                    class="btn btn-outline-primary">Download</a>
                            @endif
                            <button class="btn btn-outline-primary" type="button" data-toggle="collapse"
                                data-target="#fssaiCollapse">Update</button>
                            <div class="collapse w-100" id="fssaiCollapse">
                                <div class="card card-body">
                                    <form method="POST" enctype="multipart/form-data"
                                        action="{{ route('vendor.business-settings.update-doc') }}">
                                        @csrf
                                        <input type="hidden" name="file_type" value="fssai_doc">
                                        <div class="form-group">
                                            <label for="fssai_number">FSSAI Number</label>
                                            <input type="text" class="form-control" id="fssai_number" name="fssai_number"
                                                value="{{ $store->fssai_number ?? '' }}" placeholder="FSSAI Number">
                                        </div>
                                        <div class="form-group">
                                            <label for="fssai_doc">Upload FSSAI Document (Front)</label>
                                            <input type="file" class="form-control" id="fssai_doc" name="fssai_doc">
                                        </div>
                                        <div class="form-group">
                                            <label for="fssai_doc_back">Back side (optional)</label>
                                            <input type="file" class="form-control" id="fssai_doc_back"
                                                name="fssai_doc_back">
                                        </div>
                                        <div class="d-flex w-100 justify-content-end">
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ Other Documents (name + number + front/back, multiple) ============ --}}
    <div class="modal fade" id="otherDocsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Other Documents</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="card card-body mb-3">
                        <h6 class="mb-2">Add a Document</h6>
                        <form method="POST" enctype="multipart/form-data"
                            action="{{ route('vendor.business-settings.add-document') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Document / License Name <span class="text-danger">*</span></label>
                                    <input type="text" name="doc_name" class="form-control" required
                                        placeholder="e.g. Trade Licence">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Number</label>
                                    <input type="text" name="doc_number" class="form-control"
                                        placeholder="License / document number">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Document (Front) <span class="text-danger">*</span></label>
                                    <input type="file" name="other_doc" class="form-control" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Document (Back)</label>
                                    <input type="file" name="other_doc_back" class="form-control">
                                </div>
                                <div class="col-md-12 form-group mb-1">
                                    <label class="switch toggle-switch-sm d-inline-flex align-items-center m-0"
                                        style="gap:10px;">
                                        <input type="checkbox" class="toggle-switch-input" name="show_on_bill" value="1">
                                        <span class="toggle-switch-label"><span
                                                class="toggle-switch-indicator"></span></span>
                                        <span>Show this number on bills / labels</span>
                                    </label>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Add Document</button>
                            </div>
                        </form>
                    </div>

                    @forelse ($other_docs as $doc)
                        <div class="vdp-doc-card mb-2">
                            <div class="vdp-card-header">
                                <div class="vdp-file-icon">{{ _getFileTypeLabel($doc->file_path) }}</div>
                                <div class="vdp-card-info">
                                    <div class="vdp-doc-filename">{{ $doc->doc_name }}</div>
                                </div>
                            </div>
                            <div class="vdp-card-body">
                                <div class="vdp-info-row"><span class="vdp-info-label">Number</span><span
                                        class="vdp-info-value">{{ $doc->doc_number ?: '—' }}</span></div>
                                <div class="vdp-info-row">
                                    <span class="vdp-info-label">Status</span>
                                    @if ($doc->verified == 0)
                                        <span class="vdp-status-badge vdp-status-pending">Pending</span>
                                    @else
                                        <span class="vdp-status-badge vdp-status-approved">Approved</span>
                                    @endif
                                </div>
                            </div>
                            <div class="vdp-card-footer align-items-start flex-wrap">
                                <a href="{{ asset('storage/app/public/store/docs') . '/' . $doc->file_path }}"
                                    class="btn btn-primary">View</a>
                                @if ($doc->back_side)
                                    <a href="{{ asset('storage/app/public/store/docs') . '/' . $doc->back_side }}"
                                        class="btn btn-primary">View Back</a>
                                @endif
                                <a download href="{{ asset('storage/app/public/store/docs') . '/' . $doc->file_path }}"
                                    class="btn btn-outline-primary">Download</a>
                                <form method="POST"
                                    action="{{ route('vendor.business-settings.toggle-document', $doc->id) }}"
                                    class="d-inline-flex align-items-center m-0">
                                    @csrf
                                    <label class="switch toggle-switch-sm d-inline-flex align-items-center m-0"
                                        style="gap:8px;" title="Show on bills / labels">
                                        <input type="checkbox" class="toggle-switch-input" value="1"
                                            {{ ($doc->show_on_bill ?? false) ? 'checked' : '' }}
                                            onchange="this.form.submit()">
                                        <span class="toggle-switch-label"><span
                                                class="toggle-switch-indicator"></span></span>
                                        <span>On bill</span>
                                    </label>
                                </form>
                                <form method="POST"
                                    action="{{ route('vendor.business-settings.delete-document', $doc->id) }}"
                                    onsubmit="return confirm('Remove this document?');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No other documents added yet.</p>
                    @endforelse
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection
 
@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/shop-edit.js"></script>
    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&callback=initMap&v=3.45.8">
    </script>
    @include('admin-views.partials.tel_input')

    <script>
        let myLatlng = {
            lat: {{ $shop->latitude }},
            lng: {{ $shop->longitude }}
        };
        const map = new google.maps.Map(document.getElementById("map"), {
            zoom: 13,
            center: myLatlng,
            fullscreenControl: false, // Disable default fullscreen
        });

        let zonePolygon = null;
        let infoWindow = new google.maps.InfoWindow({
            content: "Click the map to get Lat/Lng!",
            position: myLatlng,
        });
        let bounds = new google.maps.LatLngBounds();
        let isMaximized = false;

        function initMap() {
            // Create the initial marker
            new google.maps.Marker({
                position: {
                    lat: {{ $shop->latitude }},
                    lng: {{ $shop->longitude }}
                },
                map,
                title: "{{ $shop->name }}",
            });
            infoWindow.open(map);

            const input = document.getElementById("pac-input");
            const searchBox = new google.maps.places.SearchBox(input);
            map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
            let markers = [];

            searchBox.addListener("places_changed", () => {
                const places = searchBox.getPlaces();
                if (places.length == 0) {
                    return;
                }
                // Clear out the old markers.
                markers.forEach((marker) => {
                    marker.setMap(null);
                });
                markers = [];
                // For each place, get the icon, name and location.
                const bounds = new google.maps.LatLngBounds();
                places.forEach((place) => {
                    document.getElementById('latitude').value = place.geometry.location.lat();
                    document.getElementById('longitude').value = place.geometry.location.lng();
                    if (!place.geometry || !place.geometry.location) {
                        console.log("Returned place contains no geometry");
                        return;
                    }
                    const icon = {
                        url: place.icon,
                        size: new google.maps.Size(71, 71),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(17, 34),
                        scaledSize: new google.maps.Size(25, 25),
                    };
                    // Create a marker for each place.
                    markers.push(
                        new google.maps.Marker({
                            map,
                            icon,
                            title: place.name,
                            position: place.geometry.location,
                        })
                    );

                    if (place.geometry.viewport) {
                        // Only geocodes have viewport.
                        bounds.union(place.geometry.viewport);
                    } else {
                        bounds.extend(place.geometry.location);
                    }
                });
                map.fitBounds(bounds);
            });

            // Setup custom maximize button
            setupCustomMaximize();
        }

        function setupCustomMaximize() {
            const mapDiv = document.getElementById('map');

            // Create button
            const btn = document.createElement('button');
            btn.id = 'customMaximizeBtn';
            btn.type = 'button';
            btn.style.cssText = `
                position: absolute;
                top: 10px;
                right: 10px;
                z-index: 1000;
                padding: 10px;
                background: white;
                border: none;
                cursor: pointer;
                border-radius: 2px;
                box-shadow: 0 2px 6px rgba(0,0,0,.3);
            `;
            btn.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 18 18">
                    <path d="M0 0v6h2V2h4V0H0zm16 0h-4v2h4v4h2V0h-2zm0 16h-4v2h6v-6h-2v4zM2 12H0v6h6v-2H2v-4z" fill="#666"/>
                </svg>
            `;
            mapDiv.appendChild(btn);

            btn.addEventListener('click', toggleMaximize);
        }

        function toggleMaximize() {
            const mapDiv = document.getElementById('map');
            const btn = document.getElementById('customMaximizeBtn');

            isMaximized = !isMaximized;

            if (isMaximized) {
                // Store original styles
                mapDiv.dataset.originalStyle = mapDiv.style.cssText;

                // Maximize
                mapDiv.style.cssText = `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 9999 !important;
            margin: 0 !important;
        `;

                btn.style.position = 'fixed';
                btn.style.zIndex = '10001';

                // Change icon to minimize
                btn.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 18 18">
                <path d="M6 0v2H2v4H0V0h6zm10 0v6h-2V2h-4V0h6zM6 16H2v-4H0v6h6v-2zm10 0v2h-6v-2h4v-4h2v4z" fill="#666"/>
            </svg>
        `;
            } else {
                // Restore original styles
                mapDiv.style.cssText = mapDiv.dataset.originalStyle || '';

                btn.style.position = 'absolute';
                btn.style.zIndex = '1000';

                // Change icon to maximize
                btn.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 18 18">
                <path d="M0 0v6h2V2h4V0H0zm16 0h-4v2h4v4h2V0h-2zm0 16h-4v2h6v-6h-2v4zM2 12H0v6h6v-2H2v-4z" fill="#666"/>
            </svg>
        `;
            }

            // Trigger map resize after state change
            setTimeout(() => {
                google.maps.event.trigger(map, 'resize');
                map.setCenter(myLatlng);
            }, 100);
        }

        initMap();

        // Your existing zone data code
        $('.get_zone_data').on('change', function() {
            let id = $(this).val();
            $.get({
                url: 'https://admin.mychitti.net/zone/get-coordinates/' + id,
                dataType: 'json',
                success: function(data) {
                    if (zonePolygon) {
                        zonePolygon.setMap(null);
                    }
                    zonePolygon = new google.maps.Polygon({
                        paths: data.coordinates,
                        strokeColor: "#FF0000",
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: 'white',
                        fillOpacity: 0,
                    });
                    zonePolygon.setMap(map);
                    map.setCenter(data.center);
                    google.maps.event.addListener(zonePolygon, 'click', function(mapsMouseEvent) {
                        infoWindow.close();
                        infoWindow = new google.maps.InfoWindow({
                            position: mapsMouseEvent.latLng,
                            content: JSON.stringify(mapsMouseEvent.latLng.toJSON(),
                                null, 2),
                        });
                        let coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null,
                            2);
                        coordinates = JSON.parse(coordinates);

                        document.getElementById('latitude').value = coordinates['lat'];
                        document.getElementById('longitude').value = coordinates['lng'];
                        infoWindow.open(map);
                    });
                },
            });
        });

        $(document).on('ready', function() {
            let id = $('#choice_zones').val();
            $.get({
                url: 'https://admin.mychitti.net/zone/get-coordinates/' + id,
                dataType: 'json',
                success: function(data) {
                    if (zonePolygon) {
                        zonePolygon.setMap(null);
                    }
                    zonePolygon = new google.maps.Polygon({
                        paths: data.coordinates,
                        strokeColor: "#FF0000",
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: 'white',
                        fillOpacity: 0,
                    });
                    zonePolygon.setMap(map);
                    zonePolygon.getPaths().forEach(function(path) {
                        path.forEach(function(latlng) {
                            bounds.extend(latlng);
                            map.fitBounds(bounds);
                        });
                    });
                    map.setCenter(data.center);
                    google.maps.event.addListener(zonePolygon, 'click', function(mapsMouseEvent) {
                        infoWindow.close();
                        infoWindow = new google.maps.InfoWindow({
                            position: mapsMouseEvent.latLng,
                            content: JSON.stringify(mapsMouseEvent.latLng.toJSON(),
                                null, 2),
                        });
                        let coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null,
                            2);
                        coordinates = JSON.parse(coordinates);

                        document.getElementById('latitude').value = coordinates['lat'];
                        document.getElementById('longitude').value = coordinates['lng'];
                        infoWindow.open(map);
                    });
                },
            });
        });
        $('#reset_btn').click(function() {
            $('#viewer').attr('src', "{{ asset('public/assets/admin/img/upload.png') }}");
            $('#customFileEg1').val(null);
            $('#coverImageViewer').attr('src', "{{ asset('public/assets/admin/img/upload-img.png') }}");
            $('#coverImageUpload').val(null);
            $('#choice_zones').val(null).trigger('change');
            $('#module_id').val(null).trigger('change');
            zonePolygon.setMap(null);
            $('#coordinates').val(null);
            $('#latitude').val(null);
            $('#longitude').val(null);
        })

        let zone_id = 0;
        $('#choice_zones').on('change', function() {
            if ($(this).val()) {
                zone_id = $(this).val();
            }
        });
    </script>
@endpush
