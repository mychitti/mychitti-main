@extends('layouts.vendor.app')

@section('title', translate('messages.ad'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    #vendor_schedule_wrap { display:none; }
    .preview-thumb-wrap {
        position: relative;
        width: 80px;
        height: 100px; 
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid #ddd;
        background: #fff;
    }
    .preview-thumb-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .preview-thumb-remove {
        position: absolute;
        top: 2px;
        right: 2px;
        background: rgba(220, 53, 69, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }
    .preview-thumb-remove:hover {
        background: rgb(220, 53, 69);
    }
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
                    {{ translate('messages.ad') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="row g-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('vendor.notification.store') }}" method="post" enctype="multipart/form-data"
                            id="notification">
                            @csrf
                            <div class="row gy-3">
                                <div class="col-lg-6">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <div class="form-group mb-0">
                                                <label class="input-label"
                                                    for="exampleFormControlInput1">{{ translate('messages.title') }}</label>
                                                <input type="text" name="notification_title" class="form-control"
                                                    placeholder="{{ translate('messages.new_ad') }}" required
                                                    maxlength="191">
                                            </div>
                                        </div>
                                        <div class="col-12">
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
                                        <input type="hidden" name="tergat" value="customer">
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
                                        <label class="d-block text-center mt-auto mb-0 font-weight-bold">
                                            {{ translate('messages.image') }}
                                            <small class="text-danger">* (Ratio 1080x1350, you can select multiple)</small>
                                        </label>
                                        <div id="image_previews_container" class="d-flex flex-wrap justify-content-center py-3 my-auto" style="min-height: 120px;">
                                            <div class="text-center text-muted" id="placeholder_preview">
                                                <img src="{{ asset('public/assets/admin/img/900x400/1080x1350_img1.jpg') }}" style="width: 100px; opacity: 0.6; border-radius: 8px;">
                                                <p class="small mt-1 mb-0">No images selected</p>
                                            </div>
                                        </div>
                                        <div class="custom-file">
                                            <input type="file" name="images[]" id="adImagesInput"
                                                class="custom-file-input"
                                                accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*"
                                                multiple>
                                            <label class="custom-file-label"
                                                for="adImagesInput">{{ translate('messages.choose_files') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="custom-control custom-switch mr-3">
                                            <input type="checkbox" class="custom-control-input" id="vendor_schedule_toggle">
                                            <label class="custom-control-label" for="vendor_schedule_toggle">Schedule for later</label>
                                        </div>
                                    </div>
                                    <div id="vendor_schedule_wrap" class="mb-3">
                                        <label class="input-label">Schedule Date &amp; Time <span class="text-danger">*</span></label>
                                        <input type="datetime-local" id="vendor_scheduled_at" class="form-control"
                                            min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                                    </div>
                                    <div class="btn--container justify-content-end">
                                        <button type="reset" id="reset_btn"
                                            class="btn btn--reset">{{ translate('messages.reset') }}</button>
                                        <button type="submit" id="submit"
                                            class="btn btn--primary">{{ translate('messages.submit_ad') }}</button>
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
                            <h5 class="card-title">{{ translate('Ad list') }}<span
                                    class="badge badge-soft-dark ml-2">{{ $notifications->total() }}</span></h5>
                            <form class="search-form">
                                <!-- Search -->
                                <div class="input-group input--group min--270">
                                    <input type="search" name="search" class="form-control"
                                        value="{{ request()?->search ?? null }}"
                                        placeholder="{{ translate('messages.search_ad') }}">
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
                                    <th class="border-0">{{ translate('messages.target') }}</th>
                                    <th class="border-0">{{ translate('messages.status') }}</th>
                                    <th class="border-0">{{ translate('Schedule') }}</th>
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
                                            @php
                                                $images = [];
                                                if ($notification->images) {
                                                    $images = is_array($notification->images) ? $notification->images : json_decode($notification->images, true);
                                                } elseif ($notification->image) {
                                                    $images = [$notification->image];
                                                }
                                            @endphp
                                            @if(count($images) > 0)
                                                <div class="d-flex align-items-center">
                                                    @foreach(array_slice($images, 0, 3) as $imgKey => $img)
                                                        <img class="onerror-image" 
                                                             style="aspect-ratio: 5 / 6; width: 35px; border-radius: 4px; object-fit: cover; border: 1.5px solid #fff; margin-left: {{ $imgKey > 0 ? '-12px' : '0' }}; z-index: {{ 10 - $imgKey }}; box-shadow: 0 2px 4px rgba(0,0,0,0.15);"
                                                             src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                                 $img ?? '',
                                                                 asset('storage/app/public/notification') . '/' . $img,
                                                                 asset('public/assets/admin/img/900x400/1080x1350_img1.jpg'),
                                                                 'notification/',
                                                             ) }}"
                                                             data-onerror-image="{{ asset('public/assets/admin/img/900x400/1080x1350_img1.jpg') }}">
                                                    @endforeach
                                                    @if(count($images) > 3)
                                                        <span class="badge badge-soft-dark d-flex align-items-center justify-content-center ml-1" 
                                                              style="font-size: 10px; padding: 2px 4px; border-radius: 3px; z-index: 11; font-weight: bold;">
                                                            +{{ count($images) - 3 }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <label class="badge badge-soft-warning">{{ translate('No Image') }}</label>
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
                                            @if($notification->is_scheduled)
                                                <span class="badge badge-soft-info" title="Scheduled At">{{ \Carbon\Carbon::parse($notification->scheduled_at)->format('Y-m-d H:i') }}</span>
                                                <button type="button" class="btn btn-xs btn-outline-info ml-1 reschedule-btn" 
                                                        data-id="{{ $notification->id }}" 
                                                        data-date="{{ \Carbon\Carbon::parse($notification->scheduled_at)->format('Y-m-d\TH:i') }}"
                                                        title="Reschedule ad">
                                                    <i class="tio-time"></i>
                                                </button>
                                            @else
                                                <span class="badge badge-soft-secondary">Instant</span>
                                                <button type="button" class="btn btn-xs btn-outline-info ml-1 reschedule-btn" 
                                                        data-id="{{ $notification->id }}" 
                                                        data-date=""
                                                        title="Schedule ad">
                                                    <i class="tio-time"></i>
                                                </button>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn--container justify-content-center">
                                                <a class="btn action-btn btn--primary btn-outline-primary"
                                                    href="{{ route('vendor.notification.edit', [$notification['id']]) }}"
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
                                                    action="{{ route('vendor.notification.delete', [$notification['id']]) }}"
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
            <!-- End Table -->
        </div>
    </div>

@include('vendor-views.partials.image-cropper-modal')

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1" role="dialog" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rescheduleModalLabel">Reschedule Ad</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="post" id="rescheduleForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="input-label">Select Date &amp; Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="scheduled_at" id="reschedule_scheduled_at" class="form-control" required
                            min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                        <small class="text-muted mt-2 d-block">Rescheduling will reset the status to pending until admin approves it again.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn--primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/notification.js"></script>
    <script>
        "use strict";

        var selectedAdFiles = [];

        $('#adImagesInput').on('change', function(e) {
            var files = e.target.files;
            
            // Add new files to our array
            for (var i = 0; i < files.length; i++) {
                selectedAdFiles.push(files[i]);
            }
            
            renderPreviews();
        });

        function renderPreviews() {
            var container = $('#image_previews_container');
            container.empty();
            
            if (selectedAdFiles.length === 0) {
                container.append(`
                    <div class="text-center text-muted" id="placeholder_preview">
                        <img src="{{ asset('public/assets/admin/img/900x400/1080x1350_img1.jpg') }}" style="width: 100px; opacity: 0.6; border-radius: 8px;">
                        <p class="small mt-1 mb-0">No images selected</p>
                    </div>
                `);
                updateFileInput();
                return;
            }

            selectedAdFiles.forEach(function(file, index) {
                var reader = new FileReader();
                var wrap = $('<div class="preview-thumb-wrap m-1"></div>');
                var img = $('<img src="" alt="preview">');
                var btn = $('<button type="button" class="preview-thumb-remove">&times;</button>');
                
                btn.on('click', function() {
                    selectedAdFiles.splice(index, 1);
                    renderPreviews();
                });

                wrap.append(img).append(btn);
                container.append(wrap);

                reader.onload = function(e) {
                    img.attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            });

            updateFileInput();
        }

        function updateFileInput() {
            var dt = new DataTransfer();
            selectedAdFiles.forEach(function(file) {
                dt.items.add(file);
            });
            document.getElementById('adImagesInput').files = dt.files;
            
            var labelText = selectedAdFiles.length > 0 
                ? selectedAdFiles.length + ' files selected' 
                : '{{ translate('messages.choose_files') }}';
            $('#adImagesInput').siblings('.custom-file-label').html(labelText);
        }

        // Schedule toggle
        $('#vendor_schedule_toggle').on('change', function() {
            if ($(this).is(':checked')) {
                $('#vendor_schedule_wrap').slideDown(200);
                $('#submit').html('<i class="tio-time mr-1"></i> Schedule Ad');
            } else {
                $('#vendor_schedule_wrap').slideUp(200);
                $('#submit').html('{{ translate('messages.submit_ad') }}');
            }
        });

        $('#notification').on('submit', function(e) {
            e.preventDefault();
            var isScheduled = $('#vendor_schedule_toggle').is(':checked');
            var scheduledAt = $('#vendor_scheduled_at').val();

            if (isScheduled && !scheduledAt) {
                toastr.error('Please select a schedule date & time.');
                return;
            }

            var confirmTitle = isScheduled ? 'Schedule Ad?' : '{{ translate('messages.Info') }}';
            var confirmText  = isScheduled
                ? 'This ad will be scheduled for ' + scheduledAt + ' and sent after approval.'
                : '{{ translate('messages.the ad will first be verified then it will be released') }}.';
            var confirmBtn   = isScheduled ? 'Schedule' : '{{ translate('messages.okay submit') }}';

            Swal.fire({
                title: confirmTitle,
                text: confirmText,
                type: 'info',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: 'primary',
                cancelButtonText: '{{ translate('messages.cancel') }}',
                confirmButtonText: confirmBtn,
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                    });

                    var postUrl = isScheduled
                        ? '{{ route('vendor.notification.schedule') }}'
                        : '{{ route('vendor.notification.store') }}';

                    var formData = new FormData(this);
                    if (isScheduled) {
                        formData.append('scheduled_at', scheduledAt);
                    }

                    $.post({
                        url: postUrl,
                        data: formData,
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: function(data) {
                            if (data.errors) {
                                for (var i = 0; i < data.errors.length; i++) {
                                    toastr.error(data.errors[i].message, { CloseButton: true, ProgressBar: true });
                                }
                            } else {
                                toastr.success(data.message || 'Submitted successfully!', { CloseButton: true, ProgressBar: true });
                                $('#vendor_schedule_toggle').prop('checked', false).trigger('change');
                                selectedAdFiles = [];
                                renderPreviews();
                                $('#notification')[0].reset();
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
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
            selectedAdFiles = [];
            renderPreviews();
            $('#vendor_schedule_toggle').prop('checked', false).trigger('change');
        });

        // Reschedule modal trigger
        $('.reschedule-btn').on('click', function() {
            var id = $(this).data('id');
            var date = $(this).data('date');
            
            var url = '{{ route('vendor.notification.reschedule', ['id' => ':id']) }}';
            url = url.replace(':id', id);
            
            $('#rescheduleForm').attr('action', url);
            $('#reschedule_scheduled_at').val(date);
            $('#rescheduleModal').modal('show');
        });
    </script>
@endpush
