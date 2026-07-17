@extends('layouts.admin.app')

@section('title', $store->name)

@push('css_or_js')
    <!-- Custom styles for this page -->
    <link href="{{ asset('public/assets/admin/css/croppie.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lightgallery.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lightgallery.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-thumbnail.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-video.css">
    <!-- LightGallery JS -->
    <style>
        .revw_thumbnail {
            width: 41px !important;
            border: 1px solid #dedede;
            height: 38px !important;
            margin: 4px;
            border-radius: 5px;
            cursor: zoom-in;
        }
        .otp-input {
            width: 55px;
            height: 55px;
             margin: 3px;
            text-align: center;
             font-size: 22px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .otp-input:focus { border-color: #007bff; outline: none; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">

        @include('admin-views.vendor.view.partials._header', ['store' => $store])

        <!-- Page Heading -->

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title m-0 d-flex align-items-center">
                    <span class="card-header-icon mr-2">
                        <i class="tio-blocked"></i>
                    </span>
                    <span class="ml-1">{{ translate('messages.account_suspension') }}</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3 ">
                    <div class="col-lg-6">
                        <div class="resturant--info-address">
                            @if ($store->suspended == 1)
                            <div>
                                <h1 class="text-danger">Account Suspended</h1>
                               <b> Reason:</b> <p >{{$store->suspension_reason	}}</p>
                               @php $empInfo = _getOneWhere('admins', ['id' => $store->status_updated_by]) @endphp
                               @if($empInfo )
                               <b> Suspended By:</b> <p >{{$empInfo->f_name . ' ' . $empInfo->l_name }}</p>
                               @endif
                            </div>

                            @else
                                <div class="alert alert-danger w-100" style="    background-color: #ffdce6;color: #c50606;"
                                    role="alert">
                                    This action cannot be undone
                                </div>
                                <form enctype="multipart/form-data" action="{{ route('admin.store.suspend-account') }}"
                                    method="post">
                                    @csrf
                                    <input type="hidden" name="store_id" value="{{ $store->id }}">
                                    <div class="form-row">
                                        <label for="validationTooltip04">Documents (Image or pdf only)</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" multiple name="file[]"
                                                id="customFile">
                                            <label class="custom-file-label" for="customFile">Choose file</label>
                                        </div>
                                        <div class="form-group w-100">
                                            <label for="validationTooltip04">Reason (min 20 chars)</label>
                                            <textarea class="form-control" name="reason" id="exampleFormControlTextarea1" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <button class="btn btn-danger" type="submit">Suspend Account</button>
                                </form>
                            @endif
                        </div>
                    </div>
                    @if ($store->suspension_docs)
                        <div class="col-lg-6">
                            <h5>Suspension Documents</h5>
                            <div class=" d-flex flex-column">
                                <div class="lightgallery d-flex flex-column">

                                    @foreach (json_decode($store->suspension_docs) as $value)
                                        @if (in_array(pathinfo($value, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png']))
                                            <a href="{{ asset('storage/app/public/store/docs/' . $value) }}"><img
                                                    src="{{ asset('storage/app/public/store/docs/' . $value) }}"
                                                    class="revw_thumbnail" alt="">{{ $value }}</a>
                                        @endif
                                    @endforeach
                                </div>
                                <div class=" d-flex">
                                    @foreach (json_decode($store->suspension_docs) as $value)
                                        @if (pathinfo($value, PATHINFO_EXTENSION) == 'pdf')
                                            <!-- PDF Preview with Google Docs Viewer -->
                                            <a href="{{ asset('storage/app/public/vendor/documents/' . $value) }}"
                                                target="_blank">
                                                <img src="{{ asset('storage/app/public/pdf.jpg') }}" class="revw_thumbnail"
                                                    alt="pdf">{{ $value }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ============================ RESET INVENTORY ============================ --}}
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title m-0 d-flex align-items-center">
                    <span class="card-header-icon mr-2"><i class="tio-refresh"></i></span>
                    <span class="ml-1">{{ translate('messages.Reset Inventory') }}</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-danger w-100" style="background-color:#ffdce6;color:#c50606;" role="alert">
                    This permanently deletes all purchase bills, sales orders and branch stock for this
                    store, and resets every item's on-hand stock to zero. The item catalogue is kept.
                    This action cannot be undone.
                </div>
                <button type="button" class="btn btn-outline-danger reset_inventory_btn">
                    {{ translate('messages.Reset Inventory') }}
                </button>
                <button class="send_inv_otp h-0 w-0 p-0 border-0" data-toggle="modal"
                    data-target="#resetInventoryOtpModal"></button>
            </div>
        </div>

    </div>

    {{-- OTP verification modal --}}
    <div class="modal fade" id="resetInventoryOtpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Verify OTP</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container py-4">
                        <h2 class="text-center">Enter OTP</h2>
                        <div class="p-4 bg-light rounded" style="max-width:550px;margin:0 auto;">
                            <form class="otpForm" style="margin:0 auto;"
                                action="{{ route('admin.store.reset-inventory') }}" method="post">
                                @csrf
                                <input type="hidden" name="store_id" value="{{ $store->id }}">
                                <p>OTP sent to the registered business phone number.</p>
                                <div class="d-flex justify-content-center w-100">
                                    <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                    <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                    <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                    <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                </div>
                                <button type="submit" class="btn btn-lg btn-block btn-danger mt-3">Proceed</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/lightgallery.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/plugins/video/lg-video.umd.min.js"></script>
    <!-- Page level plugins -->

    <script>
        "use strict";
        // Call the dataTables jQuery plugin
        $(document).ready(function() {
            $('#dataTable').DataTable();
        });


        function request_alert(url, message) {
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = url;
                }
            })
        }

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
    <script>
        document.querySelectorAll('.lightgallery').forEach(gallery => {
            lightGallery(gallery, {
                plugins: [lgVideo], // Add lgVideo plugin
                thumbnail: true,
                animateThumb: true,
                showThumbByDefault: true,
                thumbWidth: 80,
                thumbHeight: "auto",
                videojs: true // Enable video support
            });
        });
    </script>
    <script>
        // Reset Inventory — confirm, send OTP to the business phone, then open the OTP modal.
        $(".reset_inventory_btn").on('click', function() {
            Swal.fire({
                title: '{{ translate('messages.Are you sure?') }}',
                text: 'This permanently deletes all purchase bills, sales orders and branch stock for this store, and resets every item\'s on-hand stock to zero. This action is irreversible.',
                type: 'error',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $('.send_inv_otp').click();
                }
            });
        });

        $(".send_inv_otp").on('click', function(e) {
            e.preventDefault();
            $.post({
                url: "{{ route('admin.account.send_otp') }}",
                headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                success: function(data) {
                    if (!data.status && data.message && window.toastr) {
                        toastr.error(data.message);
                    }
                },
                error: function(xhr) {
                    if (window.toastr) {
                        toastr.error(xhr.responseJSON?.message || 'Could not send OTP. Try again.');
                    }
                }
            });
        });

        // Auto-advance between the 4 OTP boxes.
        $(document).on('input', '.otp-input', function() {
            const $inputs = $('.otp-input');
            const index = $inputs.index(this);
            if (this.value.length === this.maxLength && index < $inputs.length - 1) {
                $inputs.eq(index + 1).focus();
            }
        });
    </script>
@endpush
