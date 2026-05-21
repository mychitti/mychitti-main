<style>
    @media (max-width: 767px) {
        .banner-col-hide { display: none !important; }
    }
</style>

<div class="row">

    {{-- ── Left column: Add banner + Banner list ── --}}
    <div class="col-12 col-md-6 mb-3">

        {{-- Add banner form --}}
        <form action="{{ route('vendor.banner.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-end mb-2">
                        <div class="blinkings">
                            <strong class="mr-2">{{ translate('instructions') }}</strong>
                            <div><i class="tio-info-outined"></i></div>
                            <div class="business-notes">
                                <h6><img src="{{ asset('/public/assets/admin/img/notes.png') }}" alt=""> {{ translate('Note') }}</h6>
                                <div>{{ translate('messages.Customer_will_see_there_banners_in_your_store_details_page_in_website_and_user_apps.') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Row 1: Title + Image --}}
                    <div style="display:flex;align-items:flex-start;margin-bottom:12px;">
                        <div style="flex:1;min-width:0;padding-right:10px;">
                            <label for="title" class="form-label">{{ translate('Banner_title') }}</label>
                            <input id="title" type="text" name="title" class="form-control"
                                placeholder="{{ translate('messages.title_here...') }}" required>
                        </div>
                        <div style="width:84px;flex-shrink:0;text-align:center;">
                            <small class="d-block text-muted mb-1" style="font-size:10px;">{{ translate('Upload_Banner') }}</small>
                            <label class="m-0 d-inline-block position-relative" style="cursor:pointer;">
                                <img src="{{ asset('/public/assets/admin/img/upload-4.png') }}" id="viewer"
                                    class="border rounded" style="width:80px;height:80px;object-fit:cover;" alt="">
                                <input type="file" name="image" hidden>
                            </label>
                            <small class="d-block text-muted" style="font-size:9px;">3:1 jpg/png</small>
                        </div>
                    </div>
                    {{-- Row 2: URL full width --}}
                    <div class="form-group mb-3">
                        <label for="default_link" class="form-label">{{ translate('Redirection_URL_/_Link') }}</label>
                        <input id="default_link" type="url" name="default_link" class="form-control"
                            placeholder="{{ translate('messages.Enter_URL') }}">
                    </div>
                    {{-- Row 3: Buttons --}}
                    <div class="d-flex justify-content-end" style="gap:8px;">
                        <button type="reset" class="btn btn--reset">{{ translate('Reset') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('Submit') }}</button>
                    </div>
                </div>
            </div>
        </form>

        {{-- Banner list --}}
        <div class="card">
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper">
                    <h5 class="card-title">
                        {{ translate('messages.banner_list') }}
                        <span class="badge badge-soft-dark ml-2">{{ $banners->count() }}</span>
                    </h5>
                    <form id="search-form" class="search-form">
                        <div class="input-group input--group">
                            <input id="datatableSearch" type="search" name="search" class="form-control"
                                placeholder="{{ translate('messages.search_by_title') }}"
                                value="{{ request()->search }}">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="table-responsive datatable-custom">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table"
                    data-hs-datatables-options='{"order":[],"orderCellsTop":true,"search":"#datatableSearch","isResponsive":false,"isShowPaging":false,"paging":false}'>
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0 banner-col-hide">{{ translate('messages.SL') }}</th>
                            <th class="border-0">{{ translate('messages.title') }}</th>
                            <th class="border-0">{{ translate('messages.banner_Image') }}</th>
                            <th class="border-0 banner-col-hide">{{ translate('messages.redirection_Link') }}</th>
                            <th class="border-0 text-center">{{ translate('messages.status') }}</th>
                            <th class="border-0 text-center">{{ translate('messages.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($banners as $key => $banner)
                            <tr>
                                <td class="banner-col-hide">{{ $key + $banners->firstItem() }}</td>
                                <td><h5 class="mb-0">{{ Str::limit($banner['title'], 25, '...') }}</h5></td>
                                <td>
                                    <img class="rounded" style="height:40px;width:auto;max-width:80px;object-fit:cover;"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($banner['image'], asset('storage/app/public/banner/').'/'.$banner['image'], asset('/public/assets/admin/img/900x400/img1.jpg'), 'banner/') }}"
                                        data-onerror-image="{{ asset('/public/assets/admin/img/900x400/img1.jpg') }}"
                                        alt="">
                                </td>
                                <td class="banner-col-hide">
                                    <a href="{{ $banner->default_link }}">{{ Str::limit($banner['default_link'], 40, '...') }}</a>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        <label class="toggle-switch toggle-switch-sm" for="statusCheckbox{{ $banner->id }}">
                                            <input type="checkbox"
                                                data-url="{{ route('vendor.banner.status_update', [$banner['id'], $banner->status ? 0 : 1]) }}"
                                                class="toggle-switch-input redirect-url"
                                                id="statusCheckbox{{ $banner->id }}"
                                                {{ $banner->status ? 'checked' : '' }}>
                                            <span class="toggle-switch-label"><span class="toggle-switch-indicator"></span></span>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                            href="{{ route('vendor.banner.edit', [$banner['id']]) }}"
                                            title="{{ translate('messages.edit_banner') }}">
                                            <i class="tio-edit"></i>
                                        </a>
                                        <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                            href="javascript:" data-id="banner-{{ $banner['id'] }}"
                                            data-message="{{ translate('Want to delete this banner ?') }}">
                                            <i class="tio-delete-outlined"></i>
                                        </a>
                                        <form action="{{ route('vendor.banner.delete', [$banner['id']]) }}" method="post" id="banner-{{ $banner['id'] }}">
                                            @csrf @method('delete')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if (count($banners) !== 0)<hr>@endif
                <div class="page-area">{!! $banners->links() !!}</div>
                @if (count($banners) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="">
                        <h5>{{ translate('no_data_found') }}</h5>
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Right column: Offer banner form + Offer banner list ── --}}
    <div class="col-12 col-md-6 mb-3">

        {{-- Offer banner form --}}
        <div class="card mb-3">
            <div class="card-body">
                <form enctype="multipart/form-data" action="{{ route('vendor.banner.store-offer-banner') }}" method="post">
                    @csrf

                    {{-- Row 1: Title + Image --}}
                    <div style="display:flex;align-items:flex-start;margin-bottom:12px;">
                        <div style="flex:1;min-width:0;padding-right:10px;">
                            <label class="input-label">{{ translate('messages.Banner_Title') }}</label>
                            <input type="text" name="title" class="form-control"
                                placeholder="{{ translate('messages.Ex:Dhamaka_Offer') }}">
                        </div>
                        <div style="width:84px;flex-shrink:0;text-align:center;">
                            <small class="d-block text-muted mb-1" style="font-size:10px;">Image</small>
                            <label class="d-inline-block m-0 position-relative" style="cursor:pointer;">
                                <img class="border rounded" id="viewer2"
                                    src="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                    style="width:80px;height:80px;object-fit:cover;" alt="">
                                <div class="icon-file-group">
                                    <div class="icon-file">
                                        <input type="file" name="banner" class="custom-file-input d-none"
                                            accept=".jpg,.png,.jpeg,.gif,.bmp,.tif,.tiff|image/*">
                                        <i class="tio-edit"></i>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                    {{-- Row 2: URL full width --}}
                    <div class="form-group mb-3">
                        <label class="input-label">{{ translate('messages.URL') }}</label>
                        <input type="text" name="url" class="form-control"
                            placeholder="{{ translate('messages.Ex:https://example.com') }}">
                    </div>
                    {{-- Row 3: Dates side by side --}}
                    <div style="display:flex;gap:8px;">
                        <div style="flex:1;min-width:0;">
                            <label class="input-label">{{ translate('messages.start_date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" id="date_from" required>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <label class="input-label">{{ translate('messages.end_date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" id="date_to" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Offer banner list --}}
        <div class="card">
            <div class="card-header py-2 border-0">
                <h5 class="card-title mb-0">
                    {{ translate('messages.offer_banner_list') }}
                    <span class="badge badge-soft-dark ml-2">{{ count($offerBanners ?? []) }}</span>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">Image / Title</th>
                            <th class="border-0 banner-col-hide">Created At</th>
                            <th class="border-0">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($banners as $key => $banner)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img class="rounded mr-2" style="height:40px;width:auto;max-width:70px;object-fit:cover;"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($banner['image'], asset('storage/app/public/banners/').'/'.$banner['image'], asset('public/assets/admin/img/900x400/img1.jpg'), 'banners/') }}"
                                            data-onerror-image="{{ asset('/public/assets/admin/img/900x400/img1.jpg') }}"
                                            alt="">
                                        <span style="font-size:13px;">{{ Str::limit($banner['title'], 20, '...') }}</span>
                                    </div>
                                </td>
                                <td class="banner-col-hide" style="font-size:12px;">{{ $banner['created_at'] }}</td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                            href="javascript:" data-id="offer-banner-{{ $banner['id'] }}"
                                            data-message="{{ translate('Want to delete this banner ?') }}">
                                            <i class="tio-delete-outlined"></i>
                                        </a>
                                        <form action="{{ route('vendor.banner.delete-offer-banner', [$banner['id']]) }}"
                                            method="post" id="offer-banner-{{ $banner['id'] }}">
                                            @csrf @method('get')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if (count($banners) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="">
                        <h5>{{ translate('no_data_found') }}</h5>
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
