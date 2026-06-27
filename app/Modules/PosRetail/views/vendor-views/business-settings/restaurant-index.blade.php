@extends('layouts.vendor.app')

@section('title', translate('messages.settings'))

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/croppie.css') }}" rel="stylesheet">
    <style>
        @media (max-width: 770px) {
            .logo_img {
                position: absolute;
                top: -163px;
                left: -14px;
                width: 69px !important;
            }

            .my-resturant--card {
                padding: 0px !important;
            }
        }

        .form-row {
            margin-top: 6px;
        }

        .ck.ck-reset {
            width: 100% !important;
        }

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
    </style>
@endpush

@section('content')
    <div class="content container-fluid pb-0">
        <div class="page-header">
            <div class="d-flex flex-wrap justify-content-between w-100">
                <h2 class="page-header-title text-capitalize my-2">
                    <img class="w--26" src="{{ asset('/public/assets/admin/img/store.png') }}" alt="public">
                    <span>
                        {{ translate('messages.my_store_info') }}
                    </span>
                </h2>
                <div class="my-2">
                    <a class="btn btn-sm btn-outline-primary d-block d-md-none" href="{{ route('vendor.shop.edit') }}"><i
                            class="tio-edit"></i></a>
                    <a class="btn btn--primary d-none d-md-block" href="{{ route('vendor.shop.edit') }}"><i
                            class="tio-edit"></i>{{ translate('messages.edit_store_information') }}</a>
                </div>
            </div>
        </div>
        <div class=" border-0 row">
            <div class="card-body p-0 col-md-6">
                @if ($shop->cover_photo)
                    <div>
                        <img class="my-restaurant-img onerror-image"
                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($shop->cover_photo, asset('storage/app/public/store/cover/') . '/' . $shop->cover_photo, asset('public/assets/admin/img/900x400/img1.jpg'), 'store/cover/') }}"
                            data-onerror-image="{{ asset('public/assets/admin/img/900x400/img1.jpg') }}">
                    </div>
                @endif
                <div class="my-resturant--card">

                    @if ($shop->image == 'def.png')
                        <div class="my-resturant--avatar">
                            <img class="border onerror-image" src="{{ asset('public/assets/back-end') }}/img/shop.png"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}" alt="User Pic">
                        </div>
                    @else
                        <div class="my-resturant--avatar onerror-image logo_img">
                            <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($shop->logo, asset('storage/app/public/store/') . '/' . $shop->logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                class="border" data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                alt="">
                        </div>
                    @endif

                    <div class="my-resturant--content">
                        <span class="d-block mb-1 pb-1">
                            <strong> {{ translate('messages.name') }} :</strong>{{ $shop->name }}
                        </span>
                        <span class="d-block mb-1 pb-1">
                            <strong>{{ translate('messages.phone') }} :</strong>
                            <a href="javascript:;" style="cursor:default;" class="textToCopy">{{ $shop->phone }}</a>
                            <button class="copy-btn bg-transparent outline-none border-0">
                                <i class="tio-copy"></i>
                            </button>
                        </span>
                        <span class="d-block mb-1 pb-1">
                            <strong>{{ translate('messages.address') }} : </strong> {{ $shop->address }}
                        </span>
                        <!-- <span class="d-block mb-1 pb-1">
                                                                                                                                                                                                        <strong>{{ translate('messages.admin_commission') }} : </strong> {{ isset($shop->comission) ? $shop->comission : \App\Models\BusinessSetting::where('key', 'admin_commission')->first()->value }}%
                                                                                                                                                                                                    </span> -->
                        <span class="d-block mb-1 pb-1">
                            <strong>{{ translate('messages.vat/tax') }} : </strong> {{ $shop->tax }}%</span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="card-body p-0 col-md-6">
                <div class="d-flex  justify-content-between border p-2 mx-2">
                    <h4 class="card-title align-items-center d-flex">
                        <img src="{{ asset('public/assets/admin/img/store.png') }}" class="w--20 mr-1" alt="">
                        <span>{{ translate('messages.store_temporarily_closed') }}</span>
                    </h4>
                    <label class="switch toggle-switch-lg m-0">
                        <input type="checkbox" class="toggle-switch-input restaurant-open-status"
                            {{ $store->active ? '' : 'checked' }}>
                        <span class="toggle-switch-label">
                            <span class="toggle-switch-indicator"></span>
                        </span>
                    </label>
                </div>
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
                        <input class="toggle-switch-input dynamic-checkbox" type="checkbox" id="announcement_status"
                            data-id="announcement_status" data-type="status"
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
                            <button type="submit" class="btn btn--primary">{{ translate('publish') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <div class="content container-fluid config-inline-remove-class p-1">
        <!-- Page Heading -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/config.png') }}" class="w--30" alt="">
                </span>
                <span>
                    {{ translate('messages.store_setup') }}
                </span>
            </h1>
        </div>
        <!-- Page Heading -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">

                    @if ($store->module->module_type == 'ecommerce')
                        <div class="col-lg-4 col-sm-6">
                            <div class="form-group m-0">
                                <label
                                    class="toggle-switch toggle-switch-sm d-flex justify-content-between border rounded px-3 form-control"
                                    for="free_delivery">
                                    <span class="pr-2">
                                        {{ translate('messages.free_delivery') }}
                                        <span data-toggle="tooltip" data-placement="right"
                                            data-original-title="{{ translate('If this option is on, customers will get free delivery') }}"
                                            class="input-label-secondary"><img
                                                src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                alt="i"></span>
                                    </span>
                                    <input type="checkbox" name="free_delivery" class="toggle-switch-input redirect-url"
                                        data-url="{{ route('vendor.business-settings.toggle-settings', [$store->id, $store->free_delivery ? 0 : 1, 'free_delivery']) }}"
                                        id="free_delivery" {{ $store->free_delivery ? 'checked' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12">
                            <div class="form-group mb-0">
                                <label
                                    class="toggle-switch toggle-switch-sm d-flex justify-content-between border border-secondary rounded px-4 form-control"
                                    for="schedule_order">
                                    <span class="pr-2">{{ translate('messages.scheduled_order') }}<span
                                            class="input-label-secondary" data-toggle="tooltip" data-placement="right"
                                            data-original-title="{{ translate('When_enabled,_store_owner_can_take_scheduled_orders_from_customers.') }}"><img
                                                src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                alt="{{ translate('messages.scheduled_order_hint') }}"></span></span>
                                    <input type="checkbox" class="toggle-switch-input redirect-url "
                                        data-url="{{ route('vendor.business-settings.toggle-settings', [$store->id, $store->schedule_order ? 0 : 1, 'schedule_order']) }}"
                                        id="schedule_order" {{ $store->schedule_order ? 'checked' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12">
                            <div class="form-group mb-0">
                                <label
                                    class="toggle-switch toggle-switch-sm d-flex justify-content-between border border-secondary rounded px-4 form-control"
                                    for="delivery">
                                    <span class="pr-2">{{ translate('messages.delivery') }}<span
                                            class="input-label-secondary" data-toggle="tooltip" data-placement="right"
                                            data-original-title="{{ translate('When_enabled,_customers_can_make_home_delivery_orders_from_this_store.') }}"><img
                                                src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                alt="{{ translate('messages.home_delivery_hint') }}"></span></span>
                                    <input type="checkbox" name="delivery" class="toggle-switch-input redirect-url "
                                        data-url="{{ route('vendor.business-settings.toggle-settings', [$store->id, $store->delivery ? 0 : 1, 'delivery']) }}"
                                        id="delivery" {{ $store->delivery ? 'checked' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12">
                            <div class="form-group mb-0">
                                <label
                                    class="toggle-switch toggle-switch-sm d-flex justify-content-between border border-secondary rounded px-4 form-control"
                                    for="take_away">
                                    <span class="pr-2 text-capitalize">{{ translate('messages.take_away') }}<span
                                            class="input-label-secondary" data-toggle="tooltip" data-placement="right"
                                            data-original-title="{{ translate('When_enabled,_customers_can_place_takeaway_orders_from_this_store.') }}"><img
                                                src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                alt="{{ translate('messages.take_away_hint') }}"></span></span>
                                    <input type="checkbox" class="toggle-switch-input redirect-url "
                                        data-url="{{ route('vendor.business-settings.toggle-settings', [$store->id, $store->take_away ? 0 : 1, 'take_away']) }}"
                                        id="take_away" {{ $store->take_away ? 'checked' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    @endif





                    @if ($store->module->module_type == 'pharmacy')
                        @php($prescription_order_status = \App\Models\BusinessSetting::where('key', 'prescription_order_status')->first())
                        @php($prescription_order_status = $prescription_order_status ? $prescription_order_status->value : 0)
                        @if ($prescription_order_status)
                            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12">
                                <div class="form-group mb-0">
                                    <label
                                        class="toggle-switch toggle-switch-sm d-flex justify-content-between border border-secondary rounded px-4 form-control"
                                        for="prescription_order">
                                        <span
                                            class="pr-2 text-capitalize">{{ translate('messages.prescription_order') }}:</span>
                                        <input type="checkbox" class="toggle-switch-input redirect-url"
                                            data-url="{{ route('vendor.business-settings.toggle-settings', [$store->id, $store->prescription_order ? 0 : 1, 'prescription_order']) }}"
                                            id="prescription_order" {{ $store->prescription_order ? 'checked' : '' }}>
                                        <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        @endif
                    @endif


                    @if (config('module.' . $store->module->module_type)['cutlery'])
                        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12">
                            <div class="form-group mb-0">
                                <label
                                    class="toggle-switch toggle-switch-sm d-flex justify-content-between border border-secondary rounded px-4 form-control"
                                    for="cutlery">
                                    <span class="pr-2 text-capitalize">{{ translate('messages.cutlery') }}</span>
                                    <input type="checkbox" class="toggle-switch-input redirect-url"
                                        data-url="{{ route('vendor.business-settings.toggle-settings', [$store->id, $store->cutlery ? 0 : 1, 'cutlery']) }}"
                                        id="cutlery" {{ $store->cutlery ? 'checked' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    @endif
                </div>
                <form action="{{ route('vendor.business-settings.update-setup', [$store['id']]) }}" method="post"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        @if ($store->module->id == 5)
                            @if (!$store->free_delivery)
                                <div class="form-group mb-0 col-md-4">
                                    <label class="input-label text-capitalize" for="minimum_order">Delivery charges apply
                                        for orders below:<span class="input-label-secondary"></label>
                                    <input type="number" id="delivery_charges_on" name="delivery_charges_on"
                                        step="0.001" min="0" max="100000" class="form-control"
                                        placeholder="100" value="{{ $store->delivery_charges_on }}">
                                </div>
                            @endif

                            <div class="form-group mb-0 col-md-4">
                                <label class="input-label text-capitalize"
                                    for="minimum_order">{{ translate('messages.minimum_order_amount') }}<span
                                        class="input-label-secondary" data-toggle="tooltip" data-placement="right"
                                        data-original-title="{{ translate('Specify_the_minimum_order_amount_required_for_customers_when_ordering_from_this_store.') }}"><img
                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                            alt="{{ translate('messages.self_delivery_hint') }}"></span></label>
                                <input type="number" id="minimum_order" name="minimum_order" step="0.001"
                                    min="0" max="100000" class="form-control" placeholder="100"
                                    value="{{ $store->minimum_order > 0 ? $store->minimum_order : '' }}">
                            </div>
                            @if (config('module.' . $store->module->module_type)['order_place_to_schedule_interval'])
                                <div class="form-group mb-0 col-md-4">
                                    <label class="input-label text-capitalize"
                                        for="order_place_to_schedule_interval">{{ translate('messages.minimum_processing_time') }}<span
                                            class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                            data-original-title="{{ translate('messages.minimum_processing_time_warning') }}"><img
                                                src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                alt="{{ translate('messages.minimum_processing_time_warning') }}"></span></label>
                                    <input type="text" id="order_place_to_schedule_interval"
                                        name="order_place_to_schedule_interval" class="form-control"
                                        value="{{ $store->order_place_to_schedule_interval }}">
                                </div>
                            @endif
                        @endif
                        <div class="form-group mb-0 col-md-4 p-1">
                            <label class="input-label text-capitalize"
                                for="minimum_delivery_time">{{ $store->module->id == 5 ? translate('messages.approx_delivery_time') : 'Response Time' }}<span
                                    class="input-label-secondary" data-toggle="tooltip" data-placement="right"
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
                                <select name="delivery_time_type" class="form-control text-capitalize" required>
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

                        @if ($store->self_delivery_system)
                            <div class="col-sm-4 col-12">
                                <div class="form-group">
                                    <label class="input-label text-capitalize"
                                        for="minimum_shipping_charge">{{ translate('messages.minimum_shipping_charge') }}
                                        ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                    </label>
                                    <input type="number" id="minimum_shipping_charge" min="0" max="99999999.99"
                                        step="0.001" name="minimum_delivery_charge" class="form-control shipping_input"
                                        value="{{ $store?->minimum_shipping_charge ?? '' }}">
                                </div>
                            </div>

                            <div class="col-sm-4 col-12">
                                <div class="form-group mt-3">
                                    <label class="input-label text-capitalize"
                                        for="per_km_delivery_charge">{{ translate('messages.delivery_charge_per_km') }}
                                        ({{ \App\CentralLogics\Helpers::currency_symbol() }})</label>
                                    <input type="number" id="per_km_delivery_charge" name="per_km_delivery_charge"
                                        step="0.001" min="0" max="100000" class="form-control"
                                        placeholder="100" value="{{ $store->per_km_shipping_charge ?? '0' }}">
                                </div>
                            </div>
                            <div class="col-sm-4 col-12">
                                <div class="form-group mt-3">
                                    <label class="input-label text-capitalize"
                                        for="maximum_shipping_charge">{{ translate('messages.maximum_delivery_charge') }}
                                        ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                        <span data-toggle="tooltip" data-placement="right"
                                            data-original-title="{{ translate('It will add a limite on total delivery charge.') }}"
                                            class="input-label-secondary"><img
                                                src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                alt="{{ translate('messages.maximum_delivery_charge') }}"></span>
                                    </label>
                                    <input type="number" id="maximum_shipping_charge" name="maximum_shipping_charge"
                                        step="0.001" min="0" max="999999999" class="form-control"
                                        placeholder="10000" value="{{ $store->maximum_shipping_charge ?? '' }}">
                                </div>
                            </div>
                        @endif

                        <div class="col-sm-{{ $store->self_delivery_system ? '4' : '4' }} p-1">
                            <div class="form-group mb-0 ">
                                <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                    for="gst_status">
                                    <span>{{ translate('messages.gst') }} <span class="form-label-secondary"
                                            data-toggle="tooltip" data-placement="right"
                                            data-original-title="{{ translate('messages.gst_status_warning') }}"><img
                                                src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                alt="{{ translate('messages.gst_status_warning') }}"></span></span>
                                    <input type="checkbox" class="toggle-switch-input" name="gst_status" id="gst_status"
                                        value="1" {{ $store->gst_status ? 'checked' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                                <input type="text" id="gst" name="gst" class="form-control"
                                    value="{{ $store->gst_code }}" {{ isset($store->gst_status) ? '' : 'readonly' }}>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="btn--container mt-3 justify-content-end">
                                <button type="reset" class="btn btn--reset">{{ translate('messages.reset') }}</button>
                                <button type="submit"
                                    class="btn btn--primary">{{ translate('messages.update') }}</button>
                            </div>
                        </div>
                    </div>
                </form>

            </div>

        </div>
        <div class="card p-3">
            <h4>Documents</h4>
            <div class="gap-2">
                <button class="btn btn-outline-primary" data-toggle="modal" data-target="#gstDocUpdateModal">GST
                    Document</button>
                <button class="btn btn-outline-primary" data-toggle="modal" data-target="#idDocUpdateModal">ID Proof
                    Document</button>
                <button class="btn btn-outline-primary" data-toggle="modal" data-target="#fssaiDocUpdateModal">FSSAI
                    Document</button>
                <button class="btn btn-outline-primary" data-toggle="modal" data-target="#otherDocsModal">Other
                    Documents</button>
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
                                        {{ $id_doc ? _getFileTypeLabel($id_doc->file_path) : 'ID' }}</div>
                                    <div class="vdp-card-info">
                                        <div class="vdp-doc-filename">ID Proof</div>
                                    </div>
                                </div>
                                <div class="vdp-card-body">
                                    <div class="vdp-info-row">
                                        <span class="vdp-info-label">File Type</span>
                                        <span
                                            class="vdp-info-value">{{ $id_doc ? _getFileTypeLabel($id_doc->file_path) : '' }}</span>
                                    </div>
                                    <div class="vdp-info-row">
                                        <span class="vdp-info-label">Status</span>
                                        <div class="gap-1 d-flex">
                                            @if ($id_doc)
                                                @if ($id_doc && $id_doc->verified == 0)
                                                    <b>Front : </b>
                                                    <span class="vdp-status-badge vdp-status-pending">Pending</span>
                                                @else
                                                    <span class="vdp-status-badge vdp-status-approved">Approved</span>
                                                @endif
                                            @endif


                                        </div>
                                    </div>
                                </div>

                                <div class="vdp-card-footer align-items-start flex-wrap">
                                    @if ($id_doc)
                                        <a href="{{ asset('storage/app/public/store/docs') . '/' . $id_doc->file_path }}"
                                            class="btn btn-primary">View Front</a>
                                        @if ($id_doc->back_side)
                                            <a href="{{ asset('storage/app/public/store/docs') . '/' . $id_doc->back_side }}"
                                                class="btn btn-primary">View Back</a>
                                        @endif
                                        <a download
                                            href="{{ asset('storage/app/public/store/docs') . '/' . $id_doc->file_path }}"
                                            class="btn btn-outline-primary">Download Front</a>
                                        @if ($id_doc->back_side)
                                            <a download
                                                href="{{ asset('storage/app/public/store/docs') . '/' . $id_doc->back_side }}"
                                                class="btn btn-outline-primary">Download Back</a>
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
                                                    <label for="id_doc">New ID Proof (Front / both sides)</label>
                                                    <input type="file" class="form-control" id="id_doc"
                                                        name="id_doc" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="id_doc_back">New ID Proof (Back side)</label>
                                                    <input type="file" class="form-control" id="id_doc_back"
                                                        name="id_doc_back" required>
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
                                        {{ $gst_doc ? _getFileTypeLabel($gst_doc->file_path) : 'GST' }}
                                    </div>
                                    <div class="vdp-card-info">
                                        <div class="vdp-doc-filename">GST Document</div>
                                    </div>
                                </div>
                                <div class="vdp-card-body">
                                    <div class="vdp-info-row">
                                        <span class="vdp-info-label">File Type</span>
                                        <span
                                            class="vdp-info-value">{{ $gst_doc ? _getFileTypeLabel($gst_doc->file_path) : '' }}</span>
                                    </div>
                                    <div class="vdp-info-row">
                                        <span class="vdp-info-label">Status</span>
                                        @if ($gst_doc)
                                            @if ($gst_doc && $gst_doc->verified == 0)
                                                <span class="vdp-status-badge vdp-status-pending">Pending</span>
                                            @else
                                                <span class="vdp-status-badge vdp-status-approved">Approved</span>
                                            @endif
                                        @endif

                                    </div>
                                </div>

                                <div class="vdp-card-footer align-items-start flex-wrap">
                                    @if ($gst_doc || $store->gst_doc)
                                        <a href="{{ asset('storage/app/public/store/docs') . '/' . $gst_doc->file_path }}"
                                            class="btn btn-primary">View</a>
                                        <a download
                                            href="{{ asset('storage/app/public/store/docs') . '/' . $gst_doc->file_path }}"
                                            class="btn btn-outline-primary">Download</a>
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
                                                    <label for="gst_doc">Upload New GST Document</label>
                                                    <input type="file" class="form-control" id="gst_doc"
                                                        name="gst_doc" required>
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
                                    <div class="vdp-file-icon">
                                        {{ $fssai_doc ? _getFileTypeLabel($fssai_doc->file_path) : 'FSSAI' }}</div>
                                    <div class="vdp-card-info">
                                        <div class="vdp-doc-filename">FSSAI Document</div>
                                    </div>
                                </div>
                                <div class="vdp-card-body">
                                    <div class="vdp-info-row">
                                        <span class="vdp-info-label">FSSAI Number</span>
                                        <span class="vdp-info-value">{{ $store->fssai_number ?? '—' }}</span>
                                    </div>
                                    <div class="vdp-info-row">
                                        <span class="vdp-info-label">File Type</span>
                                        <span
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
                                        <a download
                                            href="{{ asset('storage/app/public/store/docs') . '/' . $fssai_doc->file_path }}"
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
                                                    <input type="text" class="form-control" id="fssai_number"
                                                        name="fssai_number" value="{{ $store->fssai_number ?? '' }}"
                                                        placeholder="FSSAI Number">
                                                </div>
                                                <div class="form-group">
                                                    <label for="fssai_doc">Upload FSSAI Document (Front)</label>
                                                    <input type="file" class="form-control" id="fssai_doc"
                                                        name="fssai_doc">
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
                                        <div class="vdp-info-row">
                                            <span class="vdp-info-label">Number</span>
                                            <span class="vdp-info-value">{{ $doc->doc_number ?: '—' }}</span>
                                        </div>
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
                                        <a download
                                            href="{{ asset('storage/app/public/store/docs') . '/' . $doc->file_path }}"
                                            class="btn btn-outline-primary">Download</a>
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

        </div>


    </div>

    <!-- Create schedule modal -->

    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{ translate('messages.Create Schedule For ') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="javascript:" method="post" id="add-schedule">
                        @csrf
                        <input type="hidden" name="day" id="day_id_input">
                        <div class="form-group">
                            <label for="recipient-name"
                                class="col-form-label">{{ translate('messages.Start time') }}:</label>
                            <input type="time" id="recipient-name" class="form-control" name="start_time" required>
                        </div>
                        <div class="form-group">
                            <label for="message-text"
                                class="col-form-label">{{ translate('messages.End time') }}:</label>
                            <input type="time" id="message-text" class="form-control" name="end_time" required>
                        </div>
                        <div class="btn--container justify-content-end">
                            <button type="reset" class="btn btn--reset">{{ translate('messages.reset') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('messages.Submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script>
        "use strict";

        $(document).on('click', '.restaurant-open-status', function(event) {
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ $store->active ? translate('messages.you_want_to_temporarily_close_this_store') : translate('messages.you_want_to_open_this_store') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#00868F',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.get({
                        url: '{{ route('vendor.business-settings.update-active-status') }}',
                        contentType: false,
                        processData: false,
                        beforeSend: function() {
                            $('#loading').show();
                        },
                        success: function(data) {
                            toastr.success(data.message);
                        },
                        complete: function() {
                            $('#loading').hide();
                            location.reload();
                        },
                    });
                } else {
                    event.checked = !event.checked;
                }
            })

        });

        $(document).on('click', '.delete-schedule', function() {
            let route = $(this).data('url');
            Swal.fire({
                title: '{{ translate('Want_to_delete_this_schedule?') }}',
                text: '{{ translate('If_you_select_Yes,_the_time_schedule_will_be_deleted.') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#00868F',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.get({
                        url: route,
                        beforeSend: function() {
                            $('#loading').show();
                        },
                        success: function(data) {
                            if (data.errors) {
                                for (let i = 0; i < data.errors.length; i++) {
                                    toastr.error(data.errors[i].message, {
                                        CloseButton: true,
                                        ProgressBar: true
                                    });
                                }
                            } else {
                                $('#schedule').empty().html(data.view);
                                toastr.success(
                                    '{{ translate('messages.Schedule removed successfully') }}', {
                                        CloseButton: true,
                                        ProgressBar: true
                                    });
                            }
                        },
                        error: function() {
                            toastr.error('{{ translate('messages.Schedule not found') }}', {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        },
                        complete: function() {
                            $('#loading').hide();
                        },
                    });
                }
            })
        });


        function readURL(input) {
            if (input.files && input.files[0]) {
                let reader = new FileReader();

                reader.onload = function(e) {
                    $('#viewer').attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }
        $("#customFileEg1").change(function() {
            readURL(this);
        });

        $(document).on('ready', function() {
            $("#gst_status").on('change', function() {
                if ($("#gst_status").is(':checked')) {
                    $('#gst').removeAttr('readonly');
                } else {
                    $('#gst').attr('readonly', true);
                }
            });
        });

        $('#exampleModal').on('show.bs.modal', function(event) {
            let button = $(event.relatedTarget);
            let day_name = button.data('day');
            let day_id = button.data('dayid');
            let modal = $(this);
            modal.find('.modal-title').text('{{ translate('messages.Create Schedule For ') }} ' + day_name);
            modal.find('.modal-body input[name=day]').val(day_id);
        })

        $('#add-schedule').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('vendor.business-settings.add-schedule') }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    if (data.errors) {
                        for (let i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    } else {
                        $('#schedule').empty().html(data.view);
                        $('#exampleModal').modal('hide');
                        toastr.success('{{ translate('messages.Schedule added successfully') }}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    }
                },
                error: function(XMLHttpRequest) {
                    toastr.error(XMLHttpRequest.responseText, {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        });

        $(document).ready(function() {
            $(".copy-btn").on("click", function() {
                // Get the previous <p> or span element text
                var text = $(this).prev(".textToCopy").text().trim();
                console.log(text); // Debugging

                if (navigator.clipboard && window.isSecureContext) {
                    // Modern way to copy
                    navigator.clipboard.writeText(text).then(() => {
                        console.log("Copied successfully!");
                    }).catch(err => {
                        console.error("Clipboard copy failed", err);
                    });
                } else {
                    // Fallback for older browsers
                    var tempInput = $("<textarea>"); // Use textarea instead of input
                    $("body").append(tempInput);
                    tempInput.val(text).css({
                        position: "absolute",
                        left: "-9999px", // Hide offscreen
                    }).select();
                    document.execCommand("copy");
                    tempInput.remove();
                }
                $(this).html("Copied!");
                setTimeout(() => $(this).html('<i class="tio-copy"></i>'), 1000);
            });
        });
    </script>
@endpush
