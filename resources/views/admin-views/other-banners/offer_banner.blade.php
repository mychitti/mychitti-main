@extends('layouts.admin.app')

@section('title', translate('messages.offer_banner'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/banner.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.Add Offer Banner') }}
                </span>
            </h1>
        </div>

        <div class="card">
            <div class="card-body">
                <form enctype="multipart/form-data" action="{{ route('admin.banner.store-offer-banner') }}" method="post"
                    id="">
                    @csrf

                    <div class="row">
                        <div class="col-md-2">
                            <div class="flex-grow-1 mx-auto">

                                <label class="d-inline-block m-0 position-relative">
                                    <img class="img--136 border" id="viewer"
                                        src="{{ asset('public/assets/admin/img/upload-img.png') }}" alt="thumbnail" />
                                    <div class="icon-file-group">
                                        <div class="icon-file"><input type="file" name="banner" id="customFileEg1"
                                                class="custom-file-input d-none"
                                                accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                            <i class="tio-edit"></i>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-10 p-0 row">
                            <div class="col-md-4 p-1">
                                <div class="form-group">
                                    <label class="input-label" for="default_title">{{ translate('messages.Banner_Title') }}
                                    </label>
                                    <input type="text" name="title" id="default_title" class="form-control"
                                        placeholder="{{ translate('messages.Ex:Dhamaka_Offer') }}">
                                </div>
                            </div>
                            <div class="col-md-4 p-1">
                                <div class="form-group">
                                    <label class="input-label" for="url">{{ translate('messages.URL') }}
                                    </label>
                                    <input type="text" name="url" id="url" class="form-control"
                                        placeholder="{{ translate('messages.Ex:https://example.com') }}">
                                </div>
                            </div>
                            <div class="col-md-4 p-1">
                                <div class="form-group">
                                    <label class="input-label" for="url">{{ translate('messages.City') }}
                                    </label>
                                    <select name="zone_id" id="zone_id" class="form-control js-select2-custom">
                                        <option value="all">All</option>
                                        @foreach ($zones as $zone)
                                            <option value="{{ $zone->id }}">
                                                {{ $zone->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 p-1">
                                <div class="form-group">
                                    <label class="input-label"
                                        for="exampleFormControlInput1">{{ translate('messages.start_date') }}<span
                                            class="form-label-secondary text-danger" data-toggle="tooltip"
                                            data-placement="right"
                                            data-original-title="{{ translate('messages.Required.') }}">
                                            *
                                        </span></label>
                                    <input type="date" name="start_date" class="form-control" id="date_from" required>
                                </div>
                            </div>
                            <div class="col-md-3 p-1">
                                <div class="form-group">
                                    <label class="input-label"
                                        for="exampleFormControlInput1">{{ translate('messages.end_date') }}<span
                                            class="form-label-secondary text-danger" data-toggle="tooltip"
                                            data-placement="right"
                                            data-original-title="{{ translate('messages.Required.') }}">
                                            *
                                        </span></label>
                                    <input type="date" name="end_date" class="form-control" id="date_to" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row gx-2 gx-lg-3">

            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-header py-2 border-0">
                        <div> {{ translate('messages.Offer Banner List') }}<span class="badge badge-soft-dark ml-2"
                                id="itemCount">{{ $banners->count() }}</span>
                        </div>
                    </div>
                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table id="columnSearchDatatable"
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                            data-hs-datatables-options='{
                                    "order": [],
                                    "orderCellsTop": true,
                                    "search": "#datatableSearch",
                                    "entries": "#datatableEntries",
                                    "isResponsive": false,
                                    "isShowPaging": false,
                                    "paging": false
                                }'>
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{ translate('messages.SL') }}</th>
                                    <th class="border-0">{{ translate('messages.Added By') }}</th>
                                    <th class="border-0">{{ translate('messages.Location') }}</th>
                                    <th class="border-0">{{ translate('messages.banner') }}</th>
                                    <th class="border-0">{{ translate('messages.title') }}</th>
                                    <th class="border-0">{{ translate('messages.duration') }}</th>
                                    <th class="border-0">{{ translate('messages.approval') }}</th>
                                    <th class="border-0">{{ translate('messages.live_status') }}</th>
                                    <th class="border-0">{{ translate('messages.status') }}</th>
                                    <th class="border-0">{{ translate('messages.action') }}</th>
                                </tr>
                            </thead>

                            <tbody id="set-rows">
                                @foreach ($banners as $key => $banner)
                                    <tr>
                                        <td>{{ $key + $banners->firstItem() }}</td>
                                        <td>{{ $banner->store?->name ?? 'Admin' }}</td>
                                        <td>
                                            @if ($banner->store_id)
                                                {{ $banner->store?->zone?->name ?? 'All' }}
                                            @else
                                                {{ $banner->zoneData?->name ?? 'All' }}
                                            @endif
                                        </td>
                                        <td>
                                            <span class="media align-items-center">
                                                <img class="img--176 border w-auto h--50px rounded mr-2 onerror-image"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($banner['image'], asset('storage/app/public/banners/') . '/' . $banner['image'], asset('public/assets/admin/img/900x400/img1.jpg'), 'banners/') }}"
                                                    data-onerror-image="{{ asset('/public/assets/admin/img/100x100/3.png') }}"
                                                    alt="{{ $banner->name }} image">

                                            </span>
                                            <span class="d-block font-size-sm text-body">

                                            </span>
                                        </td>
                                        <td>
                                            <h5 title="{{ ucfirst($banner['title']) }}" class="text-hover-primary mb-0">
                                                {{ ucfirst(Str::limit($banner['title'], 25, '...')) }}</h5>
                                        </td>
                                        <td>{{ translate('messages.' . $banner['start_date']) }} to
                                            {{ translate('messages.' . $banner['end_date']) }}</td>
                                        <td>
                                            @if (!$banner->approved)
                                                <div class="btn--container justify-content-start">
                                                    @if (!$banner->approved)
                                                        <a style="width: fit-content; padding: 0 2px !important;"
                                                            class="btn action-btn btn--primary btn-outline-primary form-alert"
                                                            href="javascript:" data-id="banner-{{ $banner['id'] }}"
                                                            data-message="{{ translate('Want to approve this banner ?') }}">Approve
                                                            <i class="tio-checkmark-circle-outlined"></i>
                                                        </a>
                                                        <form
                                                            action="{{ route('admin.banner.offer.approve', [$banner['id']]) }}"
                                                            method="post" id="banner-{{ $banner['id'] }}">
                                                            @csrf @method('get')
                                                        </form>
                                                    @endif
                                                </div>
                                            @else
                                                <label class="text-success">{{ translate('Approved') }}</label>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($banner->end_date < date('Y-m-d'))
                                                <label class="badge badge-soft-danger m-0">Expired</label>
                                            @elseif(!$banner->approved)
                                                <label class="badge badge-soft-danger m-0">Not Approved</label>
                                            @elseif(!$banner->status)
                                                <label class="badge badge-soft-danger m-0">Status Off</label>
                                            @else
                                                <label class="badge badge-soft-success m-0">Live</label>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <label class="toggle-switch toggle-switch-sm"
                                                    for="statusCheckbox{{ $banner->id }}">
                                                    <input type="checkbox" data-id="statusCheckbox{{ $banner->id }}"
                                                        data-type="status"
                                                        data-image-on="{{ asset('/public/assets/admin/img/modal/basic_campaign_on.png') }}"
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal/basic_campaign_off.png') }}"
                                                        data-title-on="{{ translate('By_Turning_ON_Banner!') }}"
                                                        data-title-off="{{ translate('By_Turning_OFF_Banner!') }}"
                                                        data-text-on="<p>{{ translate('If_you_turn_on_this_status,_it_will_show_on_user_website_and_app.') }}</p>"
                                                        data-text-off="<p>{{ translate('If_you_turn_off_this_status,_it_won’t_show_on_user_website_and_app') }}</p>"
                                                        class="toggle-switch-input  dynamic-checkbox"
                                                        id="statusCheckbox{{ $banner->id }}"
                                                        {{ $banner->status ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>
                                        </td>

                                        <form
                                            action="{{ route('admin.banner.offer.status', [$banner['id'], $banner->status ? 0 : 1]) }}"
                                            method="get" id="statusCheckbox{{ $banner->id }}_form">
                                        </form>
                                        <td>
                                            <div class="btn--container justify-content-start">

                                                <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                    href="javascript:" data-id="banner-delete-{{ $banner['id'] }}"
                                                    data-message="{{ translate('Want to delete this banner ?') }}">
                                                    <i class="tio-delete-outlined"></i>
                                                </a>
                                                <form action="{{ route('admin.banner.offer.delete', [$banner['id']]) }}"
                                                    method="get" id="banner-delete-{{ $banner['id'] }}">
                                                    @csrf @method('get')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>
                    @if (count($banners) !== 0)
                        <hr>
                    @endif
                    <div class="page-area">
                        {!! $banners->links() !!}
                    </div>
                    @if (count($banners) === 0)
                        <div class="empty--data">
                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                            <h5>
                                {{ translate('no_data_found') }}
                            </h5>
                        </div>
                    @endif
                </div>
            </div>
            <!-- End Table -->
        </div>
    </div>

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/banner-index.js"></script>
    <script>
        "use strict";
        var module_id = {{ Config::get('module.current_module_id') }};

        function get_items() {
            var nurl = '{{ url('/') }}/item/get-items?module_id=' + module_id;

            if (!Array.isArray(zone_id)) {
                nurl += '&zone_id=' + zone_id;
            }

            $.get({
                url: nurl,
                dataType: 'json',
                success: function(data) {
                    $('#choice_item').empty().append(data.options);
                }
            });
        }

        $(document).on('ready', function() {

            module_id = {{ Config::get('module.current_module_id') }};
            get_items();

            $('.js-data-example-ajax').select2({
                ajax: {
                    url: '{{ url('/') }}/store/get-stores',
                    data: function(params) {
                        return {
                            q: params.term, // search term
                            zone_ids: [zone_id],
                            page: params.page,
                            module_id: module_id
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    __port: function(params, success, failure) {
                        var $request = $.ajax(params);

                        $request.then(success);
                        $request.fail(failure);

                        return $request;
                    }
                }
            });

        });

        $('#banner_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('admin.banner.store') }}",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(data) {
                    if (data.errors) {
                        for (var i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    } else {
                        toastr.success('{{ translate('messages.banner_added_successfully') }}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function() {
                            location.href = '{{ route('admin.banner.add-new') }}';
                        }, 2000);
                    }
                }
            });
        });



        $('#reset_btn').click(function() {
            $('#module_select').val(null).trigger('change');
            $('#zone').val(null).trigger('change');
            $('#store_id').val(null).trigger('change');
            $('#choice_item').val(null).trigger('change');
            $('#viewer').attr('src', '{{ asset('public/assets/admin/img/900x400/img1.jpg') }}');
        })

        $("#banner_type").on('change', function() {
            if ($(this).val() == 'store_wise') {
                $("#store_wise").show();
                $("#customer_wise").hide();
            } else {
                $("#store_wise").hide();
                $("#customer_wise").show();
            }
        })
    </script>
@endpush
