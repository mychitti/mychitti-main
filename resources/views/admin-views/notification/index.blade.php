@extends('layouts.admin.app')

@section('title', translate('messages.notification'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    #schedule_wrap { display:none; }
</style>
@endpush
 
@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/notification.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.notification') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="row g-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.notification.store') }}" method="post" enctype="multipart/form-data"
                            id="notification">
                            @csrf
                            <div class="row gy-3">
                                <div class="col-lg-6">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="form-group mb-0">
                                                <label class="input-label"
                                                    for="exampleFormControlInput1">{{ translate('messages.title') }}</label>
                                                <input type="text" name="notification_title" class="form-control"
                                                    placeholder="{{ translate('messages.new_notification') }}" required
                                                    maxlength="191">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-0">
                                                <label class="input-label"
                                                    for="exampleFormControlInput1">{{ translate('messages.zone') }}</label>
                                                <select name="zone" id="zone"
                                                    class="form-control js-select2-custom">
                                                    <option value="all">{{ translate('messages.all') }}</option>
                                                    @foreach ($zones as $zone)
                                                        <option value="{{ $zone['id'] }}">{{ $zone['name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-0">
                                                <label class="input-label"
                                                    for="tergat">{{ translate('messages.send_to') }}</label>

                                                <select name="tergat" class="form-control" id="tergat"
                                                    data-placeholder="{{ translate('messages.select_tergat') }}" required>
                                                    <option value="customer">{{ translate('messages.customer') }}</option>
                                                    <option value="deliveryman">{{ translate('messages.deliveryman') }}
                                                    </option>
                                                    <!-- <option value="store">{{ translate('messages.store') }}</option> -->
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-0">
                                                <label class="input-label"
                                                    for="exampleFormControlInput1">{{ translate('messages.link') }}</label>
                                                <input type="text" name="link" class="form-control"
                                                    placeholder="{{ translate('messages.eg.:https://mychitti.net/blog') }}" 
                                                    maxlength="191">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group mb-0">
                                                <label class="input-label"
                                                    for="exampleFormControlInput1">{{ translate('messages.description') }}</label>
                                                <textarea name="description" class="form-control" maxlength="1000" required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="h-100 d-flex flex-column">
                                        <label class="d-block text-center mt-auto mb-0">
                                            {{ translate('messages.image') }}
                                            <small class="text-danger">* ( {{ translate('messages.ratio') }} 900x300
                                                )</small>
                                        </label>
                                        <div class="text-center py-3 my-auto">
                                            <img class="img--vertical" id="viewer"
                                                src="{{ asset('public/assets/admin/img/900x400/img1.jpg') }}"
                                                alt="image" />
                                        </div>
                                        <div class="custom-file">
                                            <input type="file" name="image" id="customFileEg1"
                                                class="custom-file-input"
                                                accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                            <label class="custom-file-label"
                                                for="customFileEg1">{{ translate('messages.choose_file') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="custom-control custom-switch mr-3">
                                            <input type="checkbox" class="custom-control-input" id="schedule_toggle">
                                            <label class="custom-control-label" for="schedule_toggle">Schedule for later</label>
                                        </div>
                                    </div>
                                    <div id="schedule_wrap" class="mb-3">
                                        <label class="input-label">Schedule Date &amp; Time <span class="text-danger">*</span></label>
                                        <input type="datetime-local" id="push_scheduled_at" class="form-control"
                                            min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                                    </div>
                                    <div class="btn--container justify-content-end">
                                        <button type="reset" id="reset_btn"
                                            class="btn btn--reset">{{ translate('messages.reset') }}</button>
                                        <button type="submit" id="submit"
                                            class="btn btn--primary">{{ translate('messages.send_notification') }}</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header py-2">
                        <div class="search--button-wrapper">
                            <h5 class="card-title">{{ translate('Notification list') }}<span
                                    class="badge badge-soft-dark ml-2">{{ $notifications->total() }}</span></h5>
                            <form class="search-form">
                                <!-- Search -->
                                <div class="input-group input--group min--270">
                                    <input type="search" name="search" class="form-control"
                                        value="{{ request()?->search ?? null }}"
                                        placeholder="{{ translate('messages.search_notification') }}">
                                    <button type="submit" class="btn btn--secondary">
                                        <i class="tio-search"></i>
                                    </button>
                                </div>
                                <!-- End Search -->
                            </form>
                            @if (request()->get('search'))
                                <button type="reset" class="btn btn--primary ml-2 location-reload-to-base"
                                    data-url="{{ url()->full() }}">{{ translate('messages.reset') }}</button>
                            @endif


                            <!-- Unfold -->
                            <div class="hs-unfold mr-2">
                                <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle min-height-40"
                                    href="javascript:;"
                                    data-hs-unfold-options='{
                                            "target": "#usersExportDropdown",
                                            "type": "css-animation"
                                        }'>
                                    <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                                </a>

                                <div id="usersExportDropdown"
                                    class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">

                                    <span class="dropdown-header">{{ translate('messages.download_options') }}</span>


                                    <a id="export-excel" class="dropdown-item"
                                        href="{{ route('admin.notification.export', ['type' => 'excel', request()->getQueryString()]) }}">
                                        <img class="avatar avatar-xss avatar-4by3 mr-2"
                                            src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                            alt="Image Description">
                                        {{ translate('messages.excel') }}
                                    </a>
                                    <a id="export-csv" class="dropdown-item"
                                        href="{{ route('admin.notification.export', ['type' => 'csv', request()->getQueryString()]) }}">
                                        <img class="avatar avatar-xss avatar-4by3 mr-2"
                                            src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                            alt="Image Description">
                                        .{{ translate('messages.csv') }}
                                    </a>

                                </div>
                            </div>
                            <!-- End Unfold -->
                        </div>
                    </div>
                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table id="columnSearchDatatable"
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                            data-hs-datatables-options='{
                                 "order": [],
                                 "orderCellsTop": true,
                                 "paging": false
                               }'>
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{ translate('messages.SL') }}</th>
                                    <th class="border-0">{{ translate('messages.title') }}</th>
                                    <th class="border-0">{{ translate('messages.description') }}</th>
                                    <th class="border-0">{{ translate('messages.image') }}</th>
                                    <th class="border-0">{{ translate('messages.zone') }}</th>
                                    <th class="border-0">{{ translate('messages.tergat') }}</th>
                                    <th class="border-0">{{ translate('messages.publish status') }}</th>
                                    <th class="text-center border-0">{{ translate('messages.status') }}</th>
                                    <th class="text-center border-0">{{ translate('messages.action') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($notifications as $key => $notification)
                                    <tr>
                                        <td>{{ $key + $notifications->firstItem() }}</td>
                                        <td>
                                            <span title="{{ $notification['title'] }}"
                                                class="d-block font-size-sm text-body">
                                                {{ substr($notification['title'], 0, 25) }}
                                                {{ strlen($notification['title']) > 25 ? '...' : '' }}
                                            </span>
                                        </td>
                                        <td title="{{ $notification['description'] }}">
                                            {{ substr($notification['description'], 0, 25) }}
                                            {{ strlen($notification['description']) > 25 ? '...' : '' }}
                                        </td>
                                        <td>
                                            @if ($notification['image'] != null)
                                                <img class="h--50px onerror-image"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                        $notification['image'] ?? '',
                                                        asset('storage/app/public/notification') . '/' . $notification['image'],
                                                        asset('public/assets/admin/img/160x160/img2.jpg'),
                                                        'notification/',
                                                    ) }}"
                                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}">
                                            @else
                                                <label
                                                    class="badge badge-soft-warning">{{ translate('No Image') }}</label>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $notification->zone_id == null ? translate('messages.all') : ($notification->zone ? $notification->zone->name : translate('messages.zone_deleted')) }}
                                        </td>
                                        <td class="text-uppercase">
                                            {{ translate($notification->tergat) }}
                                        </td>
                                        <td>
                                            @if($notification->is_scheduled &&  $notification->sent_at == NULL && $notification->scheduled_at > now()) 
                                                <label class="badge badge-soft-info">Scheduled for {{ date('Y-m-d H:i', strtotime($notification->scheduled_at)) }}</label>
                                            @else
                                                <label class="badge badge-soft-success">Published</label>
                                            @endif
                                        </td>
                                        <td>
                                            <label class="toggle-switch toggle-switch-sm"
                                                for="stocksCheckbox{{ $notification->id }}">
                                                <input type="checkbox"
                                                    data-url="{{ route('admin.notification.status', [$notification['id'], $notification->status ? 0 : 1]) }}"
                                                    class="toggle-switch-input redirect-url"
                                                    id="stocksCheckbox{{ $notification->id }}"
                                                    {{ $notification->status ? 'checked' : '' }} hidden>
                                                <span class="toggle-switch-label mx-auto">
                                                    <span class="toggle-switch-indicator"></span>
                                                </span>
                                            </label>
                                        </td>
                                        <td>
                                            <div class="btn--container justify-content-center">
                                                <a class="btn action-btn btn--primary btn-outline-primary"
                                                    href="{{ route('admin.notification.edit', [$notification['id']]) }}"
                                                    title="{{ translate('messages.edit_notification') }}"><i
                                                        class="tio-edit"></i>
                                                </a>
                                                <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                    href="javascript:" data-id="notification-{{ $notification['id'] }}"
                                                    data-message="{{ translate('Want to delete this notification ?') }}"
                                                    title="{{ translate('messages.delete_notification') }}"><i
                                                        class="tio-delete-outlined"></i>
                                                </a>
                                                <form
                                                    action="{{ route('admin.notification.delete', [$notification['id']]) }}"
                                                    method="post" id="notification-{{ $notification['id'] }}">
                                                    @csrf @method('delete')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if (count($notifications) !== 0)
                        <hr>
                    @endif
                    <div class="page-area">
                        {!! $notifications->links() !!}
                    </div>
                    @if (count($notifications) === 0)
                        <div class="empty--data">
                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                            <h5>
                                {{ translate('no_data_found') }}
                            </h5>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header py-2">
                        <div class="search--button-wrapper">
                            <h5 class="card-title">{{ translate('Vendor Ad list') }}<span
                                    class="badge badge-soft-dark ml-2">{{ $vendor_ads->total() }}</span></h5>
                            <form class="search-form">
                                <!-- Search -->
                                <div class="input-group input--group min--270">
                                    <input type="vsearch" name="vsearch" class="form-control"
                                        value="{{ request()?->search ?? null }}"
                                        placeholder="{{ translate('messages.search_notification') }}">
                                    <button type="submit" class="btn btn--secondary">
                                        <i class="tio-search"></i>
                                    </button>
                                </div>
                                <!-- End Search -->
                            </form>
                            @if (request()->get('vsearch'))
                                <button type="reset" class="btn btn--primary ml-2 location-reload-to-base"
                                    data-url="{{ url()->full() }}">{{ translate('messages.reset') }}</button>
                            @endif


                            <!-- Unfold -->
                            <div class="hs-unfold mr-2">
                                <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle min-height-40"
                                    href="javascript:;"
                                    data-hs-unfold-options='{
                                            "target": "#usersExportDropdown",
                                            "type": "css-animation"
                                        }'>
                                    <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                                </a>

                                <div id="usersExportDropdown"
                                    class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">

                                    <span class="dropdown-header">{{ translate('messages.download_options') }}</span>


                                    <a id="export-excel" class="dropdown-item"
                                        href="{{ route('admin.notification.export', ['type' => 'excel', request()->getQueryString()]) }}">
                                        <img class="avatar avatar-xss avatar-4by3 mr-2"
                                            src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                            alt="Image Description">
                                        {{ translate('messages.excel') }}
                                    </a>
                                    <a id="export-csv" class="dropdown-item"
                                        href="{{ route('admin.notification.export', ['type' => 'csv', request()->getQueryString()]) }}">
                                        <img class="avatar avatar-xss avatar-4by3 mr-2"
                                            src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                            alt="Image Description">
                                        .{{ translate('messages.csv') }}
                                    </a>

                                </div>
                            </div>
                            <!-- End Unfold -->
                        </div>
                    </div>
                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table id="columnSearchDatatable"
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                            data-hs-datatables-options='{
                                 "order": [],
                                 "orderCellsTop": true,
                                 "paging": false
                               }'>
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{ translate('messages.SL') }}</th>
                                    <th class="border-0">{{ translate('messages.Vendor') }}</th>
                                    <th class="border-0">{{ translate('messages.title') }}</th>
                                    <th class="border-0">{{ translate('messages.description') }}</th>
                                    <th class="border-0">{{ translate('messages.image') }}</th>
                                    <th class="border-0">{{ translate('messages.zone') }}</th>
                                    <th class="border-0">{{ translate('messages.tergat') }}</th>
                                    <th class="text-center border-0">{{ translate('messages.status') }}</th>
                                    <th class="text-center border-0">{{ translate('messages.action') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($vendor_ads as $key => $notification)
                                    <tr>
                                        <td>{{ $key + $notifications->firstItem() }}</td>
                                        <td>
                                            @if ($notification->vendor_id)
                                                <a
                                                    href="{{ route('admin.store.view', [$notification->vendor_id]) }}">{{ _storeName($notification->vendor_id) }}</a>
                                            @endif
                                        </td>
                                        <td>
                                            <span title="{{ $notification['title'] }}"
                                                class="d-block font-size-sm text-body">
                                                {{ substr($notification['title'], 0, 25) }}
                                                {{ strlen($notification['title']) > 25 ? '...' : '' }}
                                            </span>
                                        </td>
                                        <td  title="{{ $notification['description'] }}"><a href="{{route('admin.notification.detail', [$notification['id']])}}">
                                            {{ substr($notification['description'], 0, 25) }}
                                            {{ strlen($notification['description']) > 25 ? '...' : '' }}</a>
                                        </td>
                                        <td>
                                            @if ($notification['image'] != null)
                                                <img class="onerror-image"
                                                    style="aspect-ratio: 5 / 6;width: 49px;    border-radius: 3px; object-fit:cover;"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                        $notification['image'] ?? '',
                                                        asset('storage/app/public/notification') . '/' . $notification['image'],
                                                        asset('public/assets/admin/img/900x400/1080x1350_img1.jpg'),
                                                        'notification/',
                                                    ) }}"
                                                    data-onerror-image="{{ asset('public/assets/admin/img/900x400/1080x1350_img1.jpg') }}">
                                            @else
                                                <label
                                                    class="badge badge-soft-warning">{{ translate('No Image') }}</label>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $notification->zone_id == null ? translate('messages.all') : ($notification->zone ? $notification->zone->name : translate('messages.zone_deleted')) }}
                                        </td>
                                        <td class="text-uppercase">
                                            {{ translate($notification->tergat) }}
                                        </td>
                                        <td>
                                            @if ($notification->approval == 1)
                                                <label class="badge badge-soft-success">Approved</label>
                                            @elseif($notification->approval == 2)
                                                <label class="badge badge-soft-danger">Rejected</label>
                                            @else
                                                <label class="badge badge-soft-warning">Pending</label>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn--container justify-content-center">
                                                    <a class="btn action-btn btn-primary btn-outline-primary "
                                                        href="{{route('admin.notification.detail', [$notification['id']])}}"
                                                        title="{{ translate('messages.view_notification') }}"><i
                                                            class="tio-visible-outlined"></i>
                                                    </a>
                                                @if ($notification->status == 0)
                                                    <a class="btn action-btn  btn-outline-success form-alert"
                                                        href="javascript:"
                                                        style="    width: fit-content !important;padding: 0 8px !important;"
                                                        data-id="notification_approve-{{ $notification['id'] }}"
                                                        data-message="{{ translate('Want to approve and release this notification ?') }}"
                                                        title="{{ translate('messages.approve_notification') }}"><i
                                                            class="tio-checkmark-square-outlined"></i> Approve
                                                    </a>
                                                   
                                                    <a class="btn action-btn  btn-outline-danger form-alert"
                                                        href="javascript:"
                                                        style="    width: fit-content !important;padding: 0 8px !important;"
                                                        data-id="notification_reject-{{ $notification['id'] }}"
                                                        data-message="{{ translate('Want to reject this notification ?') }}"
                                                        title="{{ translate('messages.reject_notification') }}"><i
                                                            class="tio-clear-square-outlined"></i> Reject
                                                    </a>
                                                     <form
                                                        action="{{ route('admin.notification.approval', [$notification['id'], 'approve']) }}"
                                                        method="post"
                                                        id="notification_approve-{{ $notification['id'] }}">
                                                        @csrf
                                                    </form>
                                                    <form
                                                        action="{{ route('admin.notification.approval', [$notification['id'], 'reject']) }}"
                                                        method="post"
                                                        id="notification_reject-{{ $notification['id'] }}">
                                                        @csrf
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if (count($vendor_ads) !== 0)
                        <hr>
                    @endif
                    <div class="page-area">
                        {!! $vendor_ads->links() !!}
                    </div>
                    @if (count($vendor_ads) === 0)
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
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/notification.js"></script>
    <script>
        "use strict";

        // Schedule toggle
        $('#schedule_toggle').on('change', function() {
            if ($(this).is(':checked')) {
                $('#schedule_wrap').slideDown(200);
                $('#submit').html('<i class="tio-time mr-1"></i> Schedule Notification');
            } else {
                $('#schedule_wrap').slideUp(200);
                $('#submit').html('{{ translate('messages.send_notification') }}');
            }
        });

        // Push notification form
        $('#notification').on('submit', function(e) {
            e.preventDefault();
            var isScheduled = $('#schedule_toggle').is(':checked');
            var scheduledAt = $('#push_scheduled_at').val();

            if (isScheduled && !scheduledAt) {
                toastr.error('Please select a schedule date & time.');
                return;
            }

            var confirmText = isScheduled
                ? 'Schedule this notification for ' + scheduledAt + '?'
                : '{{ translate('messages.you want to sent notification to') }}' + $('#tergat').val() + '?';

            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: confirmText,
                type: 'info',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: 'primary',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: isScheduled ? 'Schedule' : '{{ translate('messages.send') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                    }); 

                    var formData = new FormData(this);
                    if (isScheduled) {
                        formData.append('is_scheduled', '1');
                        formData.append('scheduled_at', scheduledAt);
                    }
                    $.post({
                        url: '{{ route('admin.notification.store') }}',
                        data: formData,
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: function(data) {
                            toastr.success(data.message || (isScheduled ? 'Scheduled!' : 'Sent!'));
                            if (!isScheduled) {
                                $('#notification')[0].reset();
                            }
                        },
                        error: function(xhr) {
                            var errors = xhr.responseJSON?.errors;
                            if (errors) {
                                $.each(errors, function(k, v) { toastr.error(v[0]); });
                            } else {
                                toastr.error('Something went wrong.');
                            }
                        }
                    });
                }
            });
        });

$('#reset_btn').click(function() {
            $('#zone').val('all').trigger('change');
            $('#viewer').attr('src', '{{ asset('public/assets/admin/img/900x400/img1.jpg') }}');
            $('#customFileEg1').val(null);
            $('#schedule_toggle').prop('checked', false).trigger('change');
        });
    </script>
@endpush
