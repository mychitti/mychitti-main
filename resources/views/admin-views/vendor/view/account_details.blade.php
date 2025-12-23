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
                        <i class="tio-shop-outlined"></i>
                    </span>
                    <span class="ml-1">{{ translate('messages.bank_account_details') }}</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3 ">
                    <div class="col-lg-6">
                        <div class="resturant--info-address">

                            <ul class="address-info list-unstyled list-unstyled-py-3 text-dark">

                                <li>
                                    <i class="tio-city nav-icon"></i>
                                    <span>Type</span> <span>:</span> &nbsp;
                                    <span>{{ $store->account?->payment_type }}</span>
                                </li>
                                @if ($store->account?->payment_type == 'bank')
                                    <li>
                                        <i class="tio-email nav-icon"></i>
                                        <span>Account Holder Name</span> <span>:</span> &nbsp;
                                        <span>{{ $store->account?->account_holder_name }}</span>
                                    </li>
                                    <li>
                                        <i class="tio-email nav-icon"></i>
                                        <span>Account Number</span> <span>:</span> &nbsp;
                                        <a href="javascript:;" style="cursor:default;"
                                            class="textToCopy">{{ $store->account?->account_number }}</a>
                                        <button class="copy-btn bg-transparent outline-none border-0">
                                            <i class="tio-copy"></i>
                                        </button>
                                    </li>
                                    <li>
                                        <i class="tio-email nav-icon"></i>
                                        <span>IFSC Code</span> <span>:</span> &nbsp;
                                        <span>{{ $store->account?->ifsc_code }}</span>
                                    </li>
                                @else
                                    <li>
                                        <i class="tio-email nav-icon"></i>
                                        <span>UPI ID</span> <span>:</span> &nbsp;
                                        <a href="javascript:;" style="cursor:default;"
                                            class="textToCopy">{{ $store->account?->upi_id }}</a>
                                        <button class="copy-btn bg-transparent outline-none border-0">
                                            <i class="tio-copy"></i>
                                        </button>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                    @if ($store->account?->documents)
                        <div class="col-lg-6">
                        <h5>Documents</h5>
                            <div class=" d-flex flex-column">
                                <div class="lightgallery d-flex flex-column">

                                    @foreach (json_decode($store->account?->documents) as $value)
                                        @if (in_array(pathinfo($value, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                            <a href="{{ asset('storage/app/public/vendor/documents/' . $value) }}"><img
                                                    src="{{ asset('storage/app/public/vendor/documents/' . $value) }}"
                                                    class="revw_thumbnail" alt="">{{$value}}</a>
                                        @elseif(in_array(pathinfo($value, PATHINFO_EXTENSION), ['mp4', 'webm', 'ogg']))
                                            <a class=" position-relative"
                                                data-video='{"source": [{"src":"{{ asset('storage/app/public/vendor/documents/' . $value) }}", "type":"video/mp4"}], "attributes": {"preload": false, "controls": true}}'>
                                                <img src="{{ asset('storage/app/public/video.jpg') }}"
                                                    class="revw_thumbnail" alt="Video">{{$value}}
                                                {{-- <div class="play_button"><i class="fa fa-play"></i></div> --}}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                                <div class=" d-flex">
                                    @foreach (json_decode($store->account?->documents) as $value)
                                        @if (pathinfo($value, PATHINFO_EXTENSION) == 'pdf')
                                            <!-- PDF Preview with Google Docs Viewer -->
                                            <a href="{{ asset('storage/app/public/vendor/documents/' . $value) }}"
                                                target="_blank">
                                                <img src="{{ asset('storage/app/public/pdf.jpg') }}"
                                                    class="revw_thumbnail" alt="pdf">{{$value}}
                                            </a>
                                        @elseif(!in_array(pathinfo($value, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'mp4', 'webm', 'ogg']))
                                            <!-- Other Files (Download Link) -->
                                            <a href="{{ asset('storage/app/public/vendor/documents/' . $value) }}"
                                                download>Download {{$value}}
                                                {{ $value }}</a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
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
@endpush
