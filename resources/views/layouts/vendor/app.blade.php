<!DOCTYPE html>
<?php
if (env('APP_MODE') == 'demo') {
    $site_direction = session()->get('site_direction_vendor');
} else {
    $site_direction = session()->has('vendor_site_direction') ? session()->get('vendor_site_direction') : 'ltr';
}
$country = \App\Models\BusinessSetting::where('key', 'country')->first();
$countryCode = strtolower($country ? $country->value : 'auto');

?>
<html dir="{{ $site_direction }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="{{ $site_direction === 'rtl' ? 'active' : '' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

    <!-- Title -->
    <title>@yield('title')</title>
    <!-- Favicon -->
    @php($logo = \App\Models\BusinessSetting::where(['key' => 'icon'])->first()->value)
    <link rel="shortcut icon" href="">
    <link rel="icon" type="image/x-icon" href="{{ asset('storage/business/' . $logo ?? '') }}">
    <!-- Font -->
    <link href="{{ asset('public/assets/admin/css/fonts.css') }}" rel="stylesheet">
    <!-- CSS Implementing Plugins -->
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/vendor.min.css">
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/vendor/icon-set/style.css">
    <!-- CSS Front Template -->
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script> --}}

    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/theme.minc619.css?v=1.0">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/emogi-area.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/intltelinput/css/intlTelInput.css') }}">
    @include('theme-colors')
    @stack('css_or_js')

    <script
        src="{{ asset('public/assets/admin') }}/vendor/hs-navbar-vertical-aside/hs-navbar-vertical-aside-mini-cache.js">
    </script>
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/toastr.css">
    <style>
        /* Fix Google autocomplete going behind map */
        .pac-container {
            z-index: 999999 !important;
        }
    </style>
    <style>
        .created_by {}

        .dropdown-toggle:not(.dropdown-toggle-empty)::after {
            display: none;
        }

        {{-- .text_black {
            color: black !important;
        }
.text-body{
    color: black !important;
}
        body,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        td,
        td span ,
        p,
        span,
        div {
            color: #000;
        }
        button span,
        a span{
            color: white !important;
        }  

       --}} @media (max-width : 600px) {
            .btn_sm {
                padding: 6px !important;
                font-size: 12px !important;
                white-space: nowrap;
            }

            .input_sm {
                height: 34px !important;
            }

            .page-header-title {
                font-size: 15px;
            }
        }

        .toggle-switch-label {
            min-width: 43px !important;
        }

        label {
            color: black !important;
            font-weight: 500 !important;
            font-size: 12px !important;
        }

        .navbar-vertical-container>* {
            font-size: 1rem !important;
        }

        .sub-link {
            color: #0aebff !important;
        }

        .ai-vendor-md {
            font-size: 13px;
            line-height: 1.6;
        }

        .ai-vendor-md p {
            margin-bottom: 0.35rem;
        }

        .ai-vendor-md strong {
            font-weight: 700;
        }

        .ai-vendor-md em {
            font-style: italic;
        }

        .ai-vendor-md ul,
        .ai-vendor-md ol {
            padding-left: 1.2rem;
            margin-bottom: 0.35rem;
        }

        .ai-vendor-md li {
            margin-bottom: 0.2rem;
        }

        .ai-vendor-md h1,
        .ai-vendor-md h2,
        .ai-vendor-md h3 {
            font-weight: 700;
            margin: 0.4rem 0 0.2rem;
            color: #333;
        }

        .ai-vendor-md code {
            background: rgba(0, 0, 0, .08);
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 0.85em;
        }

        .ai-vendor-md pre {
            background: rgba(0, 0, 0, .08);
            padding: 8px;
            border-radius: 4px;
            overflow-x: auto;
        }

        .ai-vendor-md a {
            color: var(--primary);
            text-decoration: underline;
        }

        .animated-btn {
            padding: 8px 16px;
            font-size: 14px;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background: linear-gradient(270deg, #ff6ec4, #7873f5, #48c6ef, #6f86d6);
            background-size: 800% 800%;
            animation: gradientMove 6s ease infinite;
            transition: transform 0.2s;
        }

        .animated-btn:hover {
            transform: scale(1.05);
        }

        @keyframes gradientMove {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }
    </style>
</head>

<body class="footer-offset ">
    @if (session()->has('impersonator_id'))
        <div
            style="position:fixed;top:0;left:0;right:0;z-index:99999;background:#e74c3c;color:#fff;text-align:center;padding:8px 16px;font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:16px;">
            <i class="tio-user-big"></i>
            Impersonating: {{ session('impersonated_label') }}
            <form action="{{ route('vendor.impersonate.stop') }}" method="POST" style="margin:0">
                @csrf
                <button type="submit"
                    style="background:#fff;color:#e74c3c;border:none;border-radius:4px;padding:3px 12px;font-weight:700;cursor:pointer;font-size:12px;">
                    ✕ Exit Impersonation
                </button>
            </form>
        </div>
        <div style="height:40px"></div>
    @endif

    @if (env('APP_MODE') == 'demo')
        <div class="direction-toggle">
            <i class="tio-settings"></i>
            <span></span>
        </div>
    @endif
    {{-- loader --}}
    <div id="toast" class="toast mb-0"> </div>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div id="loading" class="initial-hidden">
                    <div class="loading-inner">
                        <img width="200" src="{{ asset('public/assets/admin/img/loader.gif') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- loader --}}

    <!-- Builder -->
    @include('layouts.vendor.partials._front-settings')
    <!-- End Builder -->

    <!-- JS Preview mode only -->
    @include('layouts.vendor.partials._header')
    @include('layouts.vendor.partials._sidebar')
    <!-- END ONLY DEV -->

    <main id="content" role="main" class="main pointer-event">
        <div class="mobil_linke">
            <div class="d-flex gap-1  flex-wrap">
                @if (count(_quickActions()))

                    @foreach (_quickActions() as $key => $value)
                        @if ($value->route == 'vendor.customer.list')
                            <a type="button" class="text-primary quick_action2 add-customer-btn " data-toggle="modal"
                                data-target="#addCustomerModal">
                                {{ $value->name }}
                            </a>
                        @else
                            <a href="{{ route($value->route, ['add']) }}"
                                class="text-primary quick_action2">{{ $value->name }}</a>
                        @endif
                    @endforeach
                    <a href="{{ route('vendor.menu_preference') }}"><i class="tio-edit"></i></a>
                @else
                    <a class="btn btn--primary" href="{{ route('vendor.menu_preference') }}">Add Quick Actions</a>
                @endif
            </div>
        </div>

        {{-- <a type="button" class="animated-btn" type="button" data-toggle="modal" data-target="#helpModal"
            style="float: right; margin: 3px 12px; padding: 0px 15px;"> Help</a> --}}
        <a type="button" class="animated-btn" type="button" id="ai-chat-fab"
            style="float: right; margin: 3px 12px; padding: 0px 15px;"> Help</a>

        @yield('content')

        @include('layouts.vendor.partials._footer')

        <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Help</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>Need something added?</strong><br>
                            If you need a new <strong>category</strong>, <strong>unit</strong>, or have any other
                            request, let us know!
                            You can submit your requirement using the form below, and our team will review it shortly.
                        </div>
                        <form action="{{ route('vendor.requirement.submit') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf

                            <div class="form-group">
                                <label for="type">Requirement Type</label>
                                <select name="requirement_type" id="requirement_type" class="form-control">
                                    <option value="">-- Select --</option>
                                    <option value="New Category">New Category</option>
                                    <option value="New Unit">New Unit</option>
                                    <option value="New Service">New Service</option>
                                    <option value="Feature Request">Feature Request</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="description">Requirement</label>
                                <textarea name="description" id="description3" class="form-control" rows="4"
                                    placeholder="Explain your requirement" required></textarea>
                            </div>

                            <div class="form-group">
                                <label for="attachment">Attachment (Optional)</label>
                                <input type="file" name="attachment" class="form-control-file">
                            </div>

                            <button type="submit" class="btn btn-primary">Submit Requirement</button>
                        </form>

                    </div>

                </div>
            </div>
        </div>
        @if (auth('vendor')->check())
            {{-- AI Chat FAB --}}
            {{-- <button id="ai-chat-fab" title="AI Assistant"
                style="position:fixed;bottom:30px;right:30px;z-index:1050;width:56px;height:56px;border-radius:50%;
                   background:var(--primary);color:#fff;border:none;box-shadow:0 4px 12px rgba(0,0,0,.25);
                   cursor:pointer;display:flex;align-items:center;justify-content:center;transition:transform .2s">
                <i class="tio-chat-outlined" style="font-size:1.6em"></i>
            </button> --}}

            {{-- AI Chat Panel --}}
            <div id="ai-chat-panel"
                style="position:fixed;top:0;right:-400px;width:400px;max-width:100vw;height:100vh;z-index:1060;
                    background:#fff;box-shadow:-4px 0 20px rgba(0,0,0,.15);display:flex;flex-direction:column;
                    transition:right .3s ease">

                {{-- Header --}}
                <div
                    style="padding:14px 16px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
                    <strong style="font-size:15px">Support</strong>
                    <div>
                        <button type="button" class="btn btn-sm px-2 py-0" id="ai-clear-memory"
                            style="background-color:#fff;color:red;font-size:12px;border:1px solid rgba(255,255,255,.4);margin-right:6px">
                            Clear
                        </button>
                        <button type="button" id="ai-chat-close"
                            style="background:none;border:none;color:#fff;font-size:22px;cursor:pointer;padding:0">
                            &times;
                        </button>
                    </div>
                </div>

                {{-- Messages --}}
                <div id="ai-chat-box" style="flex:1;overflow-y:auto;padding:14px;background:#f7f7f8">
                    <div class="text-muted text-center" style="margin-top:40%">Start a conversation...</div>
                </div>

                {{-- Input --}}
                <div style="padding:10px 12px;border-top:1px solid #e5e5e5;background:#fff;flex-shrink:0">
                    <form id="ai-chat-form" enctype="multipart/form-data">
                        @csrf
                        <div style="display:flex;gap:6px;align-items:flex-end">
                            <textarea class="form-control" id="ai-message" name="message" placeholder="Type a message..." rows="1"
                                style="resize:none;border-radius:20px;padding:8px 14px;font-size:13px;max-height:80px;overflow-y:auto"></textarea>
                            <button class="btn btn-primary btn-sm" type="submit" id="ai-send-btn"
                                style="border-radius:50%;width:36px;height:36px;padding:0;flex-shrink:0">
                                <i class="tio-send"></i>
                            </button>
                        </div>
                        <div id="ai-attach-preview" style="display:none;flex-wrap:wrap;gap:4px;margin-top:6px"></div>
                        <div class="d-flex justify-content-between">
                            <div style="display:flex;align-items:center;gap:6px;margin-top:6px">
                                <input type="file" id="ai-fileInput" name="file" accept="image/*,.pdf"
                                    style="display:none">
                                <button type="button" class="btn btn-sm btn-light" id="ai-attachFileBtn"
                                    style="border-radius:16px;font-size:12px;padding:3px 10px">
                                    <i class="tio-attachment"></i> File
                                </button>
                                <button type="button" class="btn btn-sm btn-light" id="ai-startRecord"
                                    style="border-radius:16px;font-size:12px;padding:3px 10px">
                                    <i class="tio-mic"></i> Voice
                                </button>
                                <button type="button" class="btn btn-sm btn-danger d-none" id="ai-stopRecord"
                                    style="border-radius:16px;font-size:12px;padding:3px 10px">
                                    <i class="tio-stop"></i> Stop
                                </button>
                                
                            </div> 
<button class="badge badge-soft-danger my-1" type="button" id="ai-ask-human-btn" data-toggle="modal" data-target="#helpModal">Human Support</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
        {{-- Overlay --}}
        <div id="ai-chat-overlay"
            style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:1055;background:rgba(0,0,0,.3);
                    display:none;cursor:pointer">
        </div>


        <div class="modal fade" id="toggle-modal">
            <div class="modal-dialog status-warning-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true" class="tio-clear"></span>
                        </button>
                    </div>
                    <div class="modal-body pb-5 pt-0">
                        <div class="max-349 mx-auto mb-20">
                            <div>
                                <div class="text-center">
                                    <img id="toggle-image" alt="" class="mb-20">
                                    <h5 class="modal-title" id="toggle-title"></h5>
                                </div>
                                <div class="text-center" id="toggle-message">
                                </div>
                            </div>
                            <div class="btn--container justify-content-center">
                                <button type="button" id="toggle-ok-button"
                                    class="btn btn--primary min-w-120 confirm-Toggle"
                                    data-dismiss="modal">{{ translate('Ok') }}</button>
                                <button id="reset_btn" type="reset" class="btn btn--cancel min-w-120"
                                    data-dismiss="modal">
                                    {{ translate('Cancel') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="toggle-status-modal">
            <div class="modal-dialog status-warning-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true" class="tio-clear"></span>
                        </button>
                    </div>
                    <div class="modal-body pb-5 pt-0">
                        <div class="max-349 mx-auto mb-20">
                            <div>
                                <div class="text-center">
                                    <img id="toggle-status-image" alt="" class="mb-20">
                                    <h5 class="modal-title" id="toggle-status-title"></h5>
                                </div>
                                <div class="text-center" id="toggle-status-message">
                                </div>
                            </div>
                            <div class="btn--container justify-content-center">
                                <button type="button" id="toggle-status-ok-button"
                                    class="btn btn--primary min-w-120 confirm-Status-Toggle"
                                    data-dismiss="modal">{{ translate('Ok') }}</button>
                                <button id="reset_btn" type="reset" class="btn btn--cancel min-w-120"
                                    data-dismiss="modal">
                                    {{ translate('Cancel') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- <div class="modal fade" id="notif-popup-modal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="text-center">
                                    <h2>
                                        <i class="tio-notifications"></i>
                                        <h3 id="notf_title"></h3>
                                        <span id="notf_message"></span>
                                    </h2>
                                    <hr>
                                    <a href="{{ route('vendor.notifications') }}"
                                        class="btn btn-primary notif_url">{{ translate('messages.Ok, let me check') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="popup-modal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="text-center">
                                    <h2>
                                        <i class="tio-shopping-cart-outlined"></i>
                                        {{ translate('messages.You have new order, Check Please.') }}
                                    </h2>
                                    <hr>
                                    <button
                                        class="btn btn-primary check-order">{{ translate('messages.Ok, let me check') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}
        <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Add New <span
                                class="user_typ_text">Customer</span> </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @include('vendor-views.forms.customer_add')
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="quickAccessModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable ">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Quick Access</h5>
                        <button type="button" class="close close_jc" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form class="w-100 p-0" id="task_form" enctype="multipart/form-data"
                            action="{{ route('vendor.documents.job-card.store', ['save', 0]) }}" method="post">
                            @csrf

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary done_jc">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

    </main>
    <!-- ========== END MAIN CONTENT ========== -->

    <!-- ========== END SECONDARY CONTENTS ========== -->
    <script src="{{ asset('public/assets/admin') }}/js/custom.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/firebase.min.js"></script>
    <!-- JS Implementing Plugins -->

    @stack('script')

    <!-- JS Front -->
    <script src="{{ asset('public/assets/admin') }}/js/vendor.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/theme.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/sweet_alert.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/toastr.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/emogi-area.js"></script>

    <script src="{{ asset('public/assets/admin/js/app-blade/vendor.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    {!! Toastr::message() !!}
    <script src="{{ asset('public/assets/admin/intltelinput/js/intlTelInput.min.js') }}"></script>

    @if ($errors->any())
        <script>
            "use strict";
            @foreach ($errors->all() as $error)
                toastr.error('{{ translate($error) }}', Error, {
                    CloseButton: true,
                    ProgressBar: true
                });
            @endforeach
        </script>
    @endif


    @stack('script_2')
    <audio id="myAudio">
        <source src="{{ asset('public/assets/admin/sound/notification.mp3') }}" type="audio/mpeg">
    </audio>
    <script src="{{ asset('public/assets/admin/js/view-pages/common.js') }}"></script>

    <script>
        //var notfUrl = "{{ route('vendor.last-notification') }}";
        //
        //setInterval(function() {
        //    $.getJSON(notfUrl, function(res) {
        //
        //        if (res.data) {
        //
        //            var $notifyText = $('#notf_message');
        //            var $notifyBadge = $('.notif_count_badge');
        //
        //            $notifyText.text(res.data.message);
        //
        //            playAudio();
        //            $("#notf_title").text(res.data.title);
        //            $('#notif-popup-modal').appendTo("body").modal('show');
        //        }
        //
        //    });
        //}, 300000); // 5 minutes
        //
        //
        //var audio = document.getElementById("myAudio");
        //
        //function playAudio() {
        //    audio.play();
        //}

        function pauseAudio() {
            audio.pause();
        }
        "use strict";
        $(document).on('click', '.keep-minimized', function(event) {

            var pref = $(this).prop('checked') ? 1 : 0;

            $.ajax({
                url: "{{ route('vendor.business-settings.minimized_menu') }}",
                method: "GET",
                data: {
                    pref: pref
                },
                success: function(data) {
                    toastr.success("Preference Saved");
                    setTimeout(() => {
                        window.location.reload()
                    }, 1000);
                }
            });

        });


        $(document).on('click', '.restaurant-open-status', function(event) {
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ \App\CentralLogics\Helpers::get_store_data()->active ? translate('messages.you_want_to_temporarily_close_this_store') : translate('messages.you_want_to_open_this_store') }}',
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





        $(document).on('ready', function() {
            // $('body').css('overflow','')
            $(".direction-toggle").on("click", function() {
                if ($('html').hasClass('active')) {
                    $('html').removeClass('active')
                    setDirection(1);
                } else {
                    setDirection(0);
                    $('html').addClass('active')
                }
            });
            if ($('html').attr('dir') === "rtl") {
                $(".direction-toggle").find('span').text('Toggle LTR')
            } else {
                $(".direction-toggle").find('span').text('Toggle RTL')
            }

            function setDirection(status) {
                if (status === 1) {
                    $("html").attr('dir', 'ltr');
                    $(".direction-toggle").find('span').text('Toggle RTL')
                } else {
                    $("html").attr('dir', 'rtl');
                    $(".direction-toggle").find('span').text('Toggle LTR')
                }
                $.get({
                    url: '{{ route('vendor.site_direction') }}',
                    dataType: 'json',
                    data: {
                        status: status,
                    },
                    success: function() {},

                });
            }
        });


        function route_alert(route, message) {
            Swal.fire({
                title: '{{ translate('messages.Are you sure?') }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = route;
                }
            })
        }

        $(document).on('click', '.form-alert', function() {
            let id = $(this).data('id')
            let message = $(this).data('message')
            console.log('fsdddd84398439')
            Swal.fire({
                title: '{{ translate('messages.Are you sure?') }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $('#' + id).submit()
                }
            })
        })


        function set_filter(url, id, filter_by) {
            let nurl = new URL(url);
            nurl.searchParams.set(filter_by, id);
            location.href = nurl;
        }

        @php($fcm_credentials = \App\CentralLogics\Helpers::get_business_settings('fcm_credentials'))
        let firebaseConfig = {
            apiKey: "{{ isset($fcm_credentials['apiKey']) ? $fcm_credentials['apiKey'] : '' }}",
            authDomain: "{{ isset($fcm_credentials['authDomain']) ? $fcm_credentials['authDomain'] : '' }}",
            projectId: "{{ isset($fcm_credentials['projectId']) ? $fcm_credentials['projectId'] : '' }}",
            storageBucket: "{{ isset($fcm_credentials['storageBucket']) ? $fcm_credentials['storageBucket'] : '' }}",
            messagingSenderId: "{{ isset($fcm_credentials['messagingSenderId']) ? $fcm_credentials['messagingSenderId'] : '' }}",
            appId: "{{ isset($fcm_credentials['appId']) ? $fcm_credentials['appId'] : '' }}",
            measurementId: "{{ isset($fcm_credentials['measurementId']) ? $fcm_credentials['measurementId'] : '' }}"
        };
        firebase.initializeApp(firebaseConfig);
        const messaging = firebase.messaging();

        function startFCM() {

            messaging
                .requestPermission()
                .then(function() {
                    return messaging.getToken()

                }).then(function(response) {
                    @php($store_id = \App\CentralLogics\Helpers::get_store_id())
                    subscribeTokenToTopic(response, "store_panel_{{ $store_id }}_message");
                }).catch(function(error) {
                    console.log(error);
                });
        }

        @php($key = \App\Models\BusinessSetting::where('key', 'push_notification_key')->first())

        function subscribeTokenToTopic(token, topic) {
            fetch('https://iid.googleapis.com/iid/v1/' + token + '/rel/topics/' + topic, {
                method: 'POST',
                headers: new Headers({
                    'Authorization': 'key={{ $key ? $key->value : '' }}'
                })
            }).then(response => {
                if (response.status < 200 || response.status >= 400) {
                    throw 'Error subscribing to topic: ' + response.status + ' - ' + response.text();
                }
                console.log('Subscribed to "' + topic + '"');
            }).catch(error => {
                console.error(error);
            })
        }

        function getUrlParameter(sParam) {
            let sPageURL = window.location.search.substring(1);
            let sURLletiables = sPageURL.split('&');
            for (let i = 0; i < sURLletiables.length; i++) {
                let sParameterName = sURLletiables[i].split('=');
                if (sParameterName[0] === sParam) {
                    return sParameterName[1];
                }
            }
        }

        function conversationList() {
            $.ajax({
                url: "{{ route('vendor.message.list') }}",
                success: function(data) {
                    $('#conversation-list').empty();
                    $("#conversation-list").append(data.html);
                    let user_id = getUrlParameter('user');
                    $('.customer-list').removeClass('conv-active');
                    $('#customer-' + user_id).addClass('conv-active');
                }
            })
        }

        function conversationView() {
            let conversation_id = getUrlParameter('conversation');
            let user_id = getUrlParameter('user');
            let url = '{{ url('/') }}/message/view/' + conversation_id + '/' + user_id;
            $.ajax({
                url: url,
                success: function(data) {
                    $('#view-conversation').html(data.view);
                }
            })
        }
        @php($order_notification_type = \App\Models\BusinessSetting::where('key', 'order_notification_type')->first())
        @php($order_notification_type = $order_notification_type ? $order_notification_type->value : 'firebase')
        let order_type = 'all';
        messaging.onMessage(function(payload) {
            if (payload.data.order_id && payload.data.type === 'new_order') {
                @if (\App\CentralLogics\Helpers::employee_module_permission_check('order') && $order_notification_type == 'firebase')
                    order_type = payload.data.order_type
                    playAudio();
                    $('#popup-modal').appendTo("body").modal('show');
                @endif
            } else if (payload.data.type === 'message') {
                let conversation_id = getUrlParameter('conversation');
                let user_id = getUrlParameter('user');
                let url = '{{ url('/') }}/message/view/' + conversation_id + '/' + user_id;
                $.ajax({
                    url: url,
                    success: function(data) {
                        $('#view-conversation').html(data.view);
                    }
                })
                toastr.success('{{ translate('messages.New message arrived') }}', {
                    CloseButton: true,
                    ProgressBar: true
                });

                if ($('#conversation-list').scrollTop() === 0) {
                    conversationList();
                }
            }
        });

        @if (\App\CentralLogics\Helpers::employee_module_permission_check('order') && $order_notification_type == 'manual')
            setInterval(function() {
                $.get({
                    url: '{{ route('vendor.get-store-data') }}',
                    dataType: 'json',
                    success: function(response) {
                        let data = response.data;
                        if (data.new_pending_order > 0) {
                            order_type = 'pending';
                            playAudio();
                            $('#popup-modal').appendTo("body").modal('show');
                        } else if (data.new_confirmed_order > 0) {
                            order_type = 'confirmed';
                            playAudio();
                            $('#popup-modal').appendTo("body").modal('show');
                        }
                    },
                });
            }, 10000);
        @endif

        $('.check-order').on('click', function() {
            if (order_type) {
                location.href = '{{ url('/') }}/order/list/' + order_type;
            }
        });
        startFCM();
        conversationList();
        if (getUrlParameter('conversation')) {
            conversationView();
        }
    </script>

    <script>
        "use strict";

        const input = document.querySelector('input[type="tel"]');
        window.intlTelInput(input, {
            initialCountry: "{{ $countryCode }}",
            utilsScript: "{{ asset('public/assets/admin/intltelinput/js/utils.js') }}",
            autoInsertDialCode: true,
            nationalMode: false,
            formatOnDisplay: false,
        });


        function keepNumbersAndPlus(inputString) {
            let regex = /[0-9+]/g;
            let filteredString = inputString.match(regex);
            let result = filteredString ? filteredString.join('') : '';
            return result;
        }
        $(document).on('keyup', 'input[type="tel"]', function() {
            let input = $(this).val();
            $(this).val(keepNumbersAndPlus(input));
        });
    </script>

    <!-- IE Support -->
    <script>
        if (/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) document.write(
            '<script src="{{ asset('public/assets/admin') }}/vendor/babel-polyfill/polyfill.min.js"><\/script>');
    </script>

    @if (0 && auth('vendor_employee')->check())
        <script>
            var latitude, longitude;

            function sendLocationToServer(userId) {
                // Start interval **after** getting initial location
                setInterval(function() {
                    if (typeof latitude !== "undefined" && typeof longitude !== "undefined") {

                        $.ajax({
                            url: "/update-live-location/user/" + userId + "?latitude=" + latitude +
                                "&longitude=" + longitude,
                            type: "GET",
                            success: function(response) {
                                console.log("Location updated:", response);
                            },
                            error: function(err) {
                                console.error("Error updating location:", err);
                            }
                        });
                    } else {
                        console.warn("Latitude or longitude is undefined.");
                    }
                }, 10000); // Update every 20 seconds
            }

            // Get the user's current location
            function initLocationUpdate(userId) {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        latitude = position.coords.latitude;
                        longitude = position.coords.longitude;

                        // Start sending location after we get initial coordinates
                        sendLocationToServer(userId);
                    }, function(err) {
                        console.error("Geolocation error:", err.message);
                    });
                } else {
                    console.warn("Geolocation not supported.");
                }
            }

            $(document).ready(function() {
                const userId =
                    {{ auth('vendor_employee')->user()->id }}; // You can extract this from the page or dynamically from URL
                initLocationUpdate(userId);
            });
        </script>
    @endif

    <script>
        function toggleOffcanvas() {
            const offcanvas = document.getElementById('myOffcanvas');
            const overlay = document.getElementById('canvasOverlay');
            offcanvas.classList.toggle('active');
            overlay.classList.toggle('active');
        }
        @if (_isMenuMinimized())
            $(".has-navbar-vertical-aside").addClass('navbar-vertical-aside-mini-mode');
        @else
            $(".has-navbar-vertical-aside").removeClass('navbar-vertical-aside-mini-mode');
        @endif

        function tillPresent(elem, dataId) {
            if ($(elem).prop('checked') == true) {
                $('.end_month_' + dataId).attr('readonly', true).val('');
            } else {
                $('.end_month_' + dataId).removeAttr('readonly');
            }
        }

        function deleteNewRowEmp(rowId) {
            $(".item_row_education[data-id='" + rowId + "']").remove();
        }

        function deleteNewRowExp(rowId) {
            $(".item_row_experience[data-id='" + rowId + "']").remove();
        }

        function addMoreRowEmp(section) {

            var $lastItemRow = $('.item_row_' + section).last();

            if (!$lastItemRow.length) {
                var dataId = 1;
            } else {
                var dataId = Number($lastItemRow.data('id')) + 1;
            }

            if (section == 'education') {
                var html = `<tr class="item_row_` + section + `" data-id="` + dataId + `">
                       <td><input type="text" name="school_name[]" placeholder="School Name"
                                 class="form-control">
                         </td>
                         <td><input type="text" name="degree_diploma[]"
                                 placeholder="Degree / Diploma / Cetificate" class="form-control">
                         </td>
                         <td><input type="text" name="field_of_study[]" placeholder="Field of Study"
                                 class="form-control">
                         </td>
                         <td><input type="date" name="start_month[]" class="form-control"></td>
                         <td><input type="date" name="end_month[]" class="form-control"></td>
                         <td><input type="text" name="additional_notes[]" placeholder="Additional Notes"
                                 class="form-control"></td>
                         <td><a onclick="deleteNewRowEmp(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i
                                     class="tio-delete-outlined"></i></a></td>
                    </tr>`;

                $('.rows_parent').append(html)
            } else if (section == 'experience') {
                var html = `<tr class="item_row_` + section + `" data-id="` + dataId + `">
                  <td><input type="text" name="company_name[]" placeholder="Company Name"
                                 class="form-control">
                         </td>
                     <td><input type="text" name="occupation[]" placeholder="Role"
                                 class="form-control">
                         </td>
                       
                         <td>
                             <textarea name="summary[]" id="" class="form-control" placeholder="Summary"></textarea>
                         </td>
                         <td><input type="date" name="exp_start_date[]" class="form-control">
                          <input type="hidden" class="hidden_check" name="currently_working[` + dataId +
                    `]" value="0">
                             <input type="checkbox" onchange="tillPresent(this, ` + dataId +
                    `)" class="till_present_` + dataId + `" name="currently_working[` + dataId +
                    `]" value="1" id="">
                             <label for="">Till Present</label>
                         </td>
                         <td><input type="date" name="exp_end_date[]" class="form-control end_month_` + dataId + `"></td>
                          <input type="hidden" name="existing_exp_letter[]" value="">
                               <td>
                             <input type="file" name="experience_letter[` + (dataId - 1) + `]" id="" class="form-control" >
                         </td>
                         <td><a onclick="deleteNewRowExp(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i
                                     class="tio-delete-outlined"></i></a></td>
                    </tr>`;

                $('.rows_parent_experience').append(html)
            }

        }

        $(document).ready(function() {
            $('.js-example-tags').select2({
                tags: true,
                placeholder: "Select or add tags",
            });
        });
        $(".emergency_contact_form_btn").on('click', function() {
            var isvalid = true;
            if ($("#emergencyName").val().trim() == '') {
                toastr.error('Name is required', {
                    CloseButton: true,
                    ProgressBar: true
                });
                $("#emergencyName").addClass('is-invalid')
                isvalid = false;
            }
            if ($("#primaryPhone").val().trim() == '') {
                toastr.error('Primary Phone is required', {
                    CloseButton: true,
                    ProgressBar: true
                });
                $("#primaryPhone").addClass('is-invalid')
                isvalid = false;

            }
            if (isvalid) {
                $(".close_em_btn").click()
            }
        })
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

        function deleteDocRow(dataId) {
            $(".doc_row[data-id='" + dataId + "']").remove();

        }
        $(".add_more_doc").on('click', function() {
            let fileCount = $("input[type='file'][name^='doc_file']").length;

            if (!fileCount) {
                var dataId = 1;
            } else {
                var dataId = fileCount + 1;
            }
            console.log(fileCount)
            var html = `<div class="row doc_row" data-id="` + dataId + `">
                            <div class="form-group col-md-4 p-1">
                                <label class="input-label text-capitalize"
                                    for="role_id">Document Name</label>
                                <input type="text" name="doc_name[]"
                                    placeholder="Ex: Driving License" id=""
                                    class="form-control">
                                     <input type="hidden" name="doc_file_old[]" >
                            </div>
                            <div class="form-group col-md-4 p-1">
                                <label class="input-label text-capitalize" for="role_id">
                                    Number</label>
                                <input type="text" name="doc_number[]" id=""
                                    placeholder="Ex: DL7843724239" class="form-control">
                            </div>
                            <div class="form-group col-md-4 p-1">
                                <label class="input-label text-capitalize"
                                    for="role_id">Document Upload</label>
                                    <div class="d-flex">
                                <input type="file" name="doc_file[` + (dataId - 1) + `]" id=""
                                    class="form-control">
                                    <a onclick="deleteDocRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i
                                     class="tio-delete-outlined"></i></a>
                                    </div>

                            </div>
                        </div>`;
            $(".other_documents").append(html)
        })

        function toasterNotification(msg) {
            $("#toast").text(msg);
            $("#toast").addClass("show");
            setTimeout(function() {
                $("#toast").removeClass("show");
            }, 3000);
        }

        // employee add js 
        $('#salary_type').on('change', function() {
            if ($(this).val() == 'Task-Wise') {
                $('#base_salary').val('')
                $('.base_salary_inp').hide();
            } else {
                $('.base_salary_inp').show();
            }
        })
        $(document).on('change', "#role_id", function() {
            if ($("#role_id").val() == 'add_new') {
                $("#roleAddModal").modal("show")
            }
        })
        $(document).on('change', "#department_id", function() {
            if ($("#department_id").val() == 'add_new') {
                $("#departmentAddModal").modal("show")
            }
        })
        $(document).ready(function() {
            $('#role_id, #department_id').on('select2:open', function() {
                setTimeout(function() {
                    const $addNewOption = $('li.select2-results__option[id$="-add_new"]');
                    console.log($addNewOption);

                    $addNewOption.css({
                        'color': 'rgb(13, 96, 252)',
                        'font-weight': 'bold'
                    });
                }, 0);
            });
        });
        $(".custom_ajax_form").on('submit', function(e) {
            e.preventDefault();
            if ($('.perm-checkbox:checked').length > 0) {
                $("#pos_inp").prop('checked', true);
            }
            var formData = new FormData($(this).get(0));
            if (!window.location.href.includes("custom-role/create")) {
                formData.append('form_type', 'ajax');
            }
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    if (data.status) {
                        toastr.success(data.msg);

                        $('#roleAddModal').modal('hide')
                        $('#departmentAddModal').modal('hide')
                        toasterNotification(data.msg)
                        var url = window.location.href;

                        if (data.action == 'add_role') {
                            $('.role_elem_outer').load(url + ' .role_elem_inner', function() {
                                $("#role_id").select2();
                                $('#role_id').val(data.role_id).trigger('change');
                            });
                        } else if (data.action == 'add_dept') {
                            $('.dep_elem_outer').load(url + ' .dep_elem_inner', function() {
                                $("#department_id").select2();
                                $('#department_id').val(data.dep_id).trigger('change');
                            });
                        }
                    } else if (data.status == false) {
                        toasterNotification(data.msg)
                        toastr.error(data.message);
                    } else if (data.errors) {
                        for (let i = 0; i < data.errors.length; i++) {
                            toasterNotification(data.errors[i])
                            toastr.error(data.message);

                        }
                    }
                    if (window.location.href.includes("custom-role/create")) {
                        window.location.reload();
                    }
                },
            });
        });

        // customer add js 
        function saveAddress(addr_type) {
            if (addr_type == 'billing') {
                if ($("#ba_address1").val().trim() == '') {
                    $(".ba_address1_error").text('Address line 1 is required')
                    return false;
                }
                $(".ba_address1_error").text('')
                if ($('#ba_state').val() === '' || $('#ba_state').val() === null) {
                    $(".ba_state_error").text('State is required')
                    return false;
                }
                $(".ba_state_error").text('')

                var html = '';
                html += `<b> Address Line 1 </b>` + $("#ba_address1").val() + `<br> <b> Address Line 2 </b>` + $(
                        "#ba_address2").val() + `<br> <b> State </b>` + $("#ba_state").val() +
                    `<br> <b> City </b>` + $("#ba_city").val() + `<br> <b> Pin Code </b>` + $("#ba_pincode").val()
                $("#billing_address_show").html(html)
                $("#billing_address_add").hide();
                $("#billing_address_remove").show();
            }
            if (addr_type == 'shipping') {
                if ($("#sa_address1").val().trim() == '') {
                    $(".sa_address1_error").text('Address line 1 is required')
                    return false;
                }
                $(".sa_address1_error").text('')
                if ($('#sa_state').val() === '' || $('#ba_state').val() === null) {
                    $(".sa_state_error").text('State is required')
                    return false;
                }
                $(".sa_state_error").text('')

                var html = '';
                html += `<b> Address Line 1 </b>` + $("#sa_address1").val() + `<br> <b> Address Line 2 </b>` + $(
                        "#sa_address2").val() + `<br> <b> State </b>` + $("#sa_state").val() +
                    `<br> <b> City </b>` + $("#sa_city").val() + `<br> <b> Pin Code </b>` + $("#sa_pincode").val()
                $("#shipping_address_show").html(html)
                $("#shipping_address_add").hide();
                $("#shipping_address_remove").show();
            }
        }

        function removeAddress(addr_type) {
            if (addr_type == 'billing') {
                $("#billing_address_show").html('');
                $("#billing_address_remove").hide();
                $("#billing_address_add").show();
                $("#ba_address1").val('')
                $("#ba_address2").val('')
                $("#ba_pincode").val('')
                $("#ba_city").val('')
                $("#ba_state").val('')
            } else if (addr_type == 'shipping') {
                $("#shipping_address_remove").hide();
                $("#shipping_address_add").show();
                $("#shipping_address_show").html('');
                $("#sa_address1").val('')
                $("#sa_address2").val('')
                $("#sa_pincode").val('')
                $("#sa_city").val('')
                $("#sa_state").val('')
            }
        }

        $(".rm_btn").on('click', function() {
            var data_id = $(this).attr('data-id');
            var className = data_id.replace(/^rm_/, '');
            console.log(className)
            $('.' + className).hide(); // hide element with the resulting class name
            $('.inp_' + className).val('');
            $('button[data-label="' + className + '"]').show();
        });
        $(document).ready(function() {
            console.log("{{ Route::currentRouteName() }}")
            let isTokenAdd = "{{ Route::currentRouteName() }}" === "vendor.pos.token";
            let isTaskAdd = "{{ Route::currentRouteName() }}" === "vendor.task.add";
            let isMasterLedger = "{{ Route::currentRouteName() }}" === "vendor.account.add";
            let isProject = "{{ Route::currentRouteName() }}" === "vendor.project.add";
            let isBill = "{{ Route::currentRouteName() }}" === "vendor.invoice.manual-bill";
            let isAdvancedBill = "{{ Route::currentRouteName() }}" === "vendor.invoice.create-invoice";
            let purchasedBill = "{{ Route::currentRouteName() }}" === "vendor.invoice.my-bills";
            let quotation = "{{ Route::currentRouteName() }}" === "vendor.quotation.add";
            let isChallanCreate = "{{ Route::currentRouteName() }}" === "vendor.laundry.challans.create";
            let isOrderCreate = "{{ Route::currentRouteName() }}" === "vendor.laundry.orders.create";
            let user_type =
                "{{ Route::currentRouteName() }}" == "vendor.inventory.purchase.orders" ||
                "{{ Route::currentRouteName() }}" == "vendor.laundry.challans.create" ?
                'vendor' :
                "{{ Route::currentRouteName() }}" == "vendor.account.add" ?
                'vendor_customer' :
                'customer';


            if ($('#customer_id').hasClass('get_vendor')) {
                user_type = 'vendor';
            }
            let userTypeText = (user_type == 'customer' ? 'Client' : 'Vendor');
            $(".user_typ_text").text(userTypeText)

            let url = "{{ Route::currentRouteName() }}" === "vendor.lead.add" ?
                "{{ route('vendor.customer.get-store-customer') }}" :
                "{{ route('vendor.customer.get-matches') }}?user_type=" + user_type; // website or store


            $('#customer_id').select2({
                placeholder: 'Search for a client',
                minimumInputLength: 3,
                ajax: {
                    url: url,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        let results = data.map(customer => ({
                            id: customer.id,
                            text: customer.f_name + (customer.user_type ? ' | ' + customer
                                .user_type : '') + ' (' + customer.phone + ')'
                        }));

                        if (isTokenAdd || isTaskAdd || isMasterLedger || isProject || isBill ||
                            purchasedBill || quotation || isChallanCreate ||
                            isAdvancedBill || isOrderCreate) {
                            if (isTokenAdd) {
                                // Add "Walk In" at top
                                results.unshift({
                                    id: 'walk_in',
                                    text: 'Walk In'
                                });
                            }

                            // Add "+ Add New Client" at bottom
                            results.push({
                                id: 'add_new',
                                text: '+ Add New ' + userTypeText
                            });
                        }

                        return {
                            results: results
                        };
                    },
                    cache: true
                }
            });

            // Add "Walk In" immediately in default dropdown if no search yet
            if (isTokenAdd) {
                let walkInOption = new Option('Walk In', 'walk_in', false, false);
                $('#customer_id').append(walkInOption);
            }
        });

        $(document).on('change', '#customer_id', function() {
            var selectedVal = $(this).val();
            if (selectedVal === 'add_new') {
                $('#addCustomerModal').modal('show');
                return;
            }
        });
        $(document).ready(function() {
            $('#custom-buttons2').on('click', 'button', function() {
                console.log('clicked')
                const label = $(this).data('label');
                $("." + label).show()
                console.log(label)

            })
        })

        $(document).ready(function() {
            var isMobile = /Mobi|Android/i.test(navigator.userAgent);
            let activeStream = null;
            let initializedWrappers = new Set();

            function initWebcamWrapper(wrapper) {
                const section = wrapper.find('.webcam_section');
                const openWebcamBtn = wrapper.find('.openWebcam');
                const captureBtn = wrapper.find('.capture');
                const closeBtn = wrapper.find('.closeWebcam');
                const takePhotoBtn = wrapper.find('.takePhoto');
                const webcam = section.find('.webcam')[0];
                const snapshot = section.find('.snapshot')[0];
                const previewContainer = section.find('.previewContainer');
                const hiddenFile = section.find('.hiddenFile')[0];

                if (initializedWrappers.has(wrapper[0])) return;
                initializedWrappers.add(wrapper[0]);

                let stream = null;
                let allFiles = [];

                captureBtn.hide();
                closeBtn.hide();
                takePhotoBtn.hide();

                if (isMobile) {
                    takePhotoBtn.show().on('click', function() {
                        const input = $('<input>').attr({
                            type: 'file',
                            accept: 'image/*',
                            capture: 'environment',
                            multiple: true
                        }).css('display', 'none');
                        $('body').append(input);

                        input.on('change', function(e) {
                            const files = e.target.files;
                            if (!files.length) return;

                            Array.from(files).forEach(file => {
                                allFiles.push(file);
                                const url = URL.createObjectURL(file);
                                previewContainer.append(
                                    $('<img>').attr('src', url).addClass(
                                        'preview-thumb').css({
                                        width: '60px',
                                        margin: '5px'
                                    })
                                );
                            });

                            updateHiddenInput();
                            input.remove();
                        });

                        input.click();
                    });
                } else {
                    openWebcamBtn.show();
                }

                closeBtn.on('click', function() {
                    if (stream) stream.getTracks().forEach(track => track.stop());
                    $(webcam).hide();
                    captureBtn.hide();
                    closeBtn.hide();
                    openWebcamBtn.show();

                    if (activeStream === stream) activeStream = null;
                });

                function updateHiddenInput() {
                    const dt = new DataTransfer();
                    allFiles.forEach(f => dt.items.add(f));
                    hiddenFile.files = dt.files;
                }
            }

            // initialize on load
            $('.webcam_wrapper').each(function() {
                initWebcamWrapper($(this));
            });

            // initialize new rows
            $(document).on('click', '.add_more_btn, .addWebcamRow, #addMore', function() {
                setTimeout(() => {
                    $('.webcam_wrapper').each(function() {
                        initWebcamWrapper($(this));
                    });
                }, 300);
            });

            // 🎥 Open webcam (only once bound)
            $(document).on('click', '.openWebcam', function() {
                const wrapper = $(this).closest('.webcam_wrapper');
                const webcam = wrapper.find('.webcam')[0];
                const captureBtn = wrapper.find('.capture');
                const closeBtn = wrapper.find('.closeWebcam');
                const previewContainer = wrapper.find('.previewContainer');
                const openWebcamBtn = $(this);

                if (activeStream) {
                    activeStream.getTracks().forEach(track => track.stop());
                    $('.webcam').hide();
                    $('.capture, .closeWebcam').hide();
                    $('.openWebcam').show();
                }

                navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: "user"
                        }
                    })
                    .then(stream => {
                        webcam.srcObject = stream;
                        activeStream = stream;
                        $(webcam).show();
                        captureBtn.show();
                        closeBtn.show();
                        previewContainer.empty();
                        openWebcamBtn.hide();
                    })
                    .catch(err => {
                        alert('Cannot access webcam. Check permissions and HTTPS.');
                        console.error(err);
                    });
            });

            // 📸 Capture image
            $(document).on('click', '.capture', function() {
                const wrapper = $(this).closest('.webcam_wrapper');
                const webcam = wrapper.find('.webcam')[0];
                const snapshot = wrapper.find('.snapshot')[0];
                const previewContainer = wrapper.find('.previewContainer');
                const hiddenFile = wrapper.find('.hiddenFile')[0];

                snapshot.width = webcam.videoWidth;
                snapshot.height = webcam.videoHeight;
                snapshot.getContext('2d').drawImage(webcam, 0, 0, snapshot.width, snapshot.height);

                const dataURL = snapshot.toDataURL('image/png');
                previewContainer.append(
                    $('<img>').attr('src', dataURL).addClass('preview-thumb').css({
                        width: '60px',
                        margin: '5px'
                    })
                );

                // Convert dataURL to file and push to allFiles array
                fetch(dataURL)
                    .then(res => res.blob())
                    .then(blob => {
                        const file = new File([blob], `captured-${Date.now()}.png`, {
                            type: 'image/png'
                        });
                        wrapper[0].allFiles = wrapper[0].allFiles || [];
                        wrapper[0].allFiles.push(file);

                        // Update hidden input with all files
                        const dt = new DataTransfer();
                        wrapper[0].allFiles.forEach(f => dt.items.add(f));
                        hiddenFile.files = dt.files;
                    });
            });

        });
        $(document).ready(function() {
            $(".copy-btn").on("click", function(e) {
                e.stopPropagation(); // ⛔ prevent click from bubbling to parent
                e.preventDefault();

                var text = $(this).prev(".textToCopy").text().trim();

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(() => {
                        console.log("Copied successfully!");
                    }).catch(err => {
                        console.error("Clipboard copy failed", err);
                    });
                } else {
                    var tempInput = $("<textarea>");
                    $("body").append(tempInput);
                    tempInput.val(text).css({
                        position: "absolute",
                        left: "-9999px",
                    }).select();
                    document.execCommand("copy");
                    tempInput.remove();
                }

                $(this).html("Copied!");
                setTimeout(() => $(this).html('<i class="tio-copy"></i>'), 1000);
            });
        });
        $(".reassign_modal_btn").on('click', function() {
            var id = $(this).attr('data-id');
            $(".task_id").val(id)
        })

        function addMoreRRRow(item = null) {
            console.table(item)
            var $lastItemRow = $('.item_row').last();
            if (!$lastItemRow.length) {
                var dataId = 1;
            } else {
                var dataId = Number($lastItemRow.data('id')) + 1;
            }
            var item_name = '';
            var item_id = '';
            var item_price = '';
            var item_hsn = '';
            var readonly = '';
            var model_number = '';
            if (item) {
                item_name = item.item_name ?? '';
                item_price = item.selling_price ?? 0;
                readonly = 'readonly';
                item_id = item.id;
                model_number = item.model_number ?? '';
            }
            var html = `<tr class="item_row row_` + dataId + `" data-id="` + dataId + `">
                                            <td class="p-1"><input type="file" accept="image/*" name="image[` + (
                dataId - 1) + `]" class="form-control">
                <td>  <div class="webcam_wrapper row col-md-4 p-3">
                        <div class="">
                            <label>Webcam</label><br>
                            <button type="button" class="btn btn-primary openWebcam">Open Webcam</button>
                            <button type="button" class="btn btn-primary capture" style="display:none;">Capture Photo</button>
                            <button type="button" class="btn btn-primary takePhoto" style="display:none;">Take Photo
                                (Mobile)</button>
                        </div>

                        <div class="form-row my-2 webcam_section">
                            <input type="file" name="webcam_file[${dataId - 1}][]" class="hiddenFile" multiple hidden>
                            <video class="webcam" autoplay playsinline style="display:none; width:300px;"></video>
                            <canvas class="snapshot" style="display:none;"></canvas>
                            <div class="previewContainer" style="margin-top:10px; "></div>
                        </div>
                    </div>
                </td>

    
                      <td class="p-1"><input type="text" name="pr_name[]" value="` + item_name + `" placeholder="Product Name"
                                class="form-control">
                        </td>
                        <td class="p-1"><input type="text" name="brand[]" placeholder="Brand / Model"
                                class="form-control" value="` + model_number + `">
                        </td>
                          <td class="p-1"><input type="number" step="0.001" value="` + item_price + `" name="value[]" placeholder="Value (₹)" class="form-control"></td>

                        <td class="p-1"><input type="text" name="serial_no[]"
                                placeholder="Serial No" class="form-control">
                        </td>
                        <td class="p-1">
                        <textarea name="issue_for[]" class="form-control" id=""  placeholder="Received For (Issue)"></textarea>
                        </td>
                        <td class="p-1">
                        <textarea name="accessories_given[]" id="" placeholder="Accessories Given" class="form-control"></textarea>
                        </td>

                      <td class="p-1"><button type="button" onclick="deleteRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>`;

            $('.rrrows_parent').append(html)
        }

        function addMoreJCRow(item = null) {
            var $lastItemRow = $('.item_row_jc').last();
            if (!$lastItemRow.length) {
                var dataId = 1;
            } else {
                var dataId = Number($lastItemRow.data('jc')) + 1;
            }
            if (item) {
                item_name = item.item_name;
                price = item.selling_price;
                readonly = 'readonly';
                item_id = item.id;
            } else {
                item_name = '';
                price = '';
                item_id = null;
                readonly = '';
            }
            @if (App\CentralLogics\Helpers::get_store_data()->gst &&
                    json_decode(App\CentralLogics\Helpers::get_store_data()->gst)->status)
                className = 'hidden_tax';
            @else
                className = '';
            @endif

            var html = `<tr class="item_row_jc row_` + dataId + `" data-jc="` + dataId + `">
            <input type="hidden" name="inventory_item_id[]" value="` + item_id +
                `"  class="form-control">
                      <td class="py-1"><input type="text" ` + readonly + ` name="name[]" placeholder="Name" value="` +
                item_name + `" class="form-control"></td>
                <td class="py-1"><input type="number" name="price[]" step="0.001" placeholder="Price" value="` +
                price + `" class="form-control">
                </td>
                <td class=" ` + className + ` py-1"><input type="number" name="tax[]" placeholder="Tax" class="form-control">
                </td>
                      <td class="py-1"><input type="number" name="qty[]" placeholder="Qty" class="form-control"></td>
                      <td class="py-1"><button type="button" onclick="deleteJCRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>`;

            $('.rows_parent_jc').append(html)
        }
    </script>
    <script>
        $(".quotation_number").on('keyup', function() {
            console.log('fsd')
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.get({
                url: '{{ route('vendor.quotation.quotation_number_validation') }}',
                data: {
                    number: $(this).val()
                },
                success: function(data) {
                    console.log(data.status)
                    console.log(data)
                    if (data.status) {
                        $(".quote_num_text").text('')
                    } else {
                        $(".quote_num_text").text(data.msg)

                    }
                }
            });
        })
    </script>
    <script>
        $(function() {
            var $panel = $('#ai-chat-panel');
            var $overlay = $('#ai-chat-overlay');
            var $fab = $('#ai-chat-fab');
            var $box = $('#ai-chat-box');
            var aiMediaRecorder, aiAudioChunks = [],
                aiRecordedBlob = null;
            var aiChatLoaded = false;

            function openChat() {
                $panel.css('right', '0');
                $overlay.show();
                $fab.hide();
                if (!aiChatLoaded) {
                    aiLoadHistory();
                    aiChatLoaded = true;
                }
                $('#ai-message').focus();
            }

            function closeChat() {
                $panel.css('right', '-400px');
                $overlay.hide();
                $fab.show();
            }

            $fab.on('click', openChat);
            $('#ai-chat-close, #ai-chat-overlay').on('click', closeChat);
            $('#ai-ask-human-btn').on('click', closeChat);
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') closeChat();
            });

            function aiRenderMessage(role, content) {
                var isUser = role === 'user';
                var body;
                if (isUser) {
                    body = $('<div>').text(content).html().replace(/\n/g, '<br>');
                } else {
                    body = '<div class="ai-vendor-md">' + marked.parse(content) + '</div>';
                }
                var html = '<div style="display:flex;justify-content:' + (isUser ? 'flex-end' : 'flex-start') +
                    ';margin-bottom:10px">' +
                    '<div style="max-width:82%;padding:9px 13px;border-radius:' + (isUser ? '16px 16px 4px 16px' :
                        '16px 16px 16px 4px') +
                    ';background:' + (isUser ? 'var(--primary)' : '#e9ecef') + ';color:' + (isUser ? '#fff' :
                        '#333') +
                    ';font-size:13px;line-height:1.45;word-wrap:break-word">' +
                    body + '</div></div>';
                $box.append(html);
                $box.scrollTop($box[0].scrollHeight);
            }

            function aiLoadHistory() {
                $box.html('<div class="text-muted text-center" style="margin-top:40%">Loading...</div>');
                $.get("{{ route('vendor.ai-chat.history') }}", function(res) {
                    $box.html('');
                    if (res.success && res.messages.length) {
                        res.messages.forEach(function(r) {
                            aiRenderMessage(r.role, r.content);
                        });
                    } else {
                        $box.html(
                            '<div class="text-muted text-center" style="margin-top:40%">Start a conversation...</div>'
                        );
                    }
                });
            }

            function aiShowChip(id, icon, label, onRemove) {
                $('#ai-attach-preview').css('display', 'flex');
                var chip = $('<span id="' + id +
                    '" style="display:inline-flex;align-items:center;gap:4px;border-radius:12px;padding:2px 10px;font-size:12px"></span>'
                );
                chip.append(icon + ' ' + label);
                var x = $(
                    '<button type="button" style="background:none;border:none;padding:0 0 0 4px;cursor:pointer;font-size:15px;line-height:1;color:inherit;opacity:.7">&times;</button>'
                );
                x.on('click', function() {
                    chip.remove();
                    if (!$('#ai-attach-preview').children().length) $('#ai-attach-preview').hide();
                    onRemove();
                });
                chip.append(x);
                $('#ai-attach-preview').append(chip);
            }

            $('#ai-attachFileBtn').on('click', function() {
                $('#ai-fileInput').click();
            });

            $('#ai-fileInput').on('change', function() {
                $('#ai-file-chip').remove();
                if (!$('#ai-attach-preview').children().length) $('#ai-attach-preview').hide();
                var file = this.files[0];
                if (!file) return;
                var isImg = file.type.startsWith('image/');
                var icon = isImg ? '🖼️' : '📄';
                aiShowChip('ai-file-chip', icon, file.name, function() {
                    $('#ai-fileInput').val('');
                });
            });

            $('#ai-startRecord').on('click', async function() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    alert('Voice not supported.');
                    return;
                }
                var stream = await navigator.mediaDevices.getUserMedia({
                    audio: true
                });
                aiAudioChunks = [];
                aiRecordedBlob = null;
                $('#ai-voice-chip').remove();
                if (!$('#ai-attach-preview').children().length) $('#ai-attach-preview').hide();
                aiMediaRecorder = new MediaRecorder(stream);
                aiMediaRecorder.ondataavailable = function(e) {
                    aiAudioChunks.push(e.data);
                };
                aiMediaRecorder.onstop = function() {
                    aiRecordedBlob = new Blob(aiAudioChunks, {
                        type: 'audio/webm'
                    });
                    stream.getTracks().forEach(function(t) {
                        t.stop();
                    });
                    $('#ai-voice-chip').remove();
                    aiShowChip('ai-voice-chip', '🎤', 'Voice ready', function() {
                        aiRecordedBlob = null;
                    });
                    var chip = $('#ai-voice-chip');
                    chip.css({
                        background: '#e8fde8',
                        border: '1px solid #9fd99f',
                        color: '#2d6a2d'
                    });
                };
                aiMediaRecorder.start();
                $('#ai-startRecord').addClass('d-none');
                $('#ai-stopRecord').removeClass('d-none');
                aiShowChip('ai-recording-chip', '🔴', 'Recording...', function() {});
                $('#ai-recording-chip').css({
                    background: '#fde8e8',
                    border: '1px solid #f9a0a0',
                    color: '#8b0000'
                });
            });

            $('#ai-stopRecord').on('click', function() {
                aiMediaRecorder.stop();
                $('#ai-stopRecord').addClass('d-none');
                $('#ai-startRecord').removeClass('d-none');
                $('#ai-recording-chip').remove();
                if (!$('#ai-attach-preview').children().length) $('#ai-attach-preview').hide();
            });

            $('#ai-chat-form').on('submit', async function(e) {
                e.preventDefault();
                var formData = new FormData();
                var message = $('#ai-message').val();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('message', message);
                formData.append('current_page', window.location.pathname);

                var file = $('#ai-fileInput')[0].files[0];
                if (file) formData.append('file', file);
                if (aiRecordedBlob) formData.append('voice', aiRecordedBlob, 'voice.webm');
                if (!message && !file && !aiRecordedBlob) return;

                if (message) aiRenderMessage('user', message);
                else if (file) aiRenderMessage('user', '[File: ' + file.name + ']');
                else if (aiRecordedBlob) aiRenderMessage('user', '[Voice message]');

                $('#ai-message').val('').css('height', 'auto');
                $('#ai-fileInput').val('');
                aiRecordedBlob = null;
                $('#ai-attach-preview').empty().hide();

                // Extract visible DOM structure so Sam knows exactly what elements are on the page
                try {
                    var chatEls = ['ai-chat-panel','ai-chat-overlay','ai-chat-fab'];
                    function inChat(el) { return chatEls.some(function(id){ return !!el.closest('#'+id); }); }
                    function inViewport(el) {
                        var r = el.getBoundingClientRect();
                        return r.width > 0 && r.height > 0 && r.top < window.innerHeight && r.bottom > 0;
                    }
                    function elPos(el) {
                        var r = el.getBoundingClientRect();
                        var v = Math.round((r.top + r.height/2) / window.innerHeight * 100);
                        var h = Math.round((r.left + r.width/2) / window.innerWidth * 100);
                        return (v < 33 ? 'top' : v < 66 ? 'middle' : 'bottom') + '-' + (h < 50 ? 'left' : 'right');
                    }
                    var struct = [];
                    var seen = {};

                    // Page heading
                    document.querySelectorAll('h1,h2,h3,h4,h5,.page-title,.card-title').forEach(function(el) {
                        if (inChat(el) || !inViewport(el)) return;
                        var t = el.innerText.trim().replace(/\s+/g,' ');
                        if (t && !seen[t]) { seen[t]=1; struct.push('[Heading] "' + t + '" (' + elPos(el) + ')'); }
                    });

                    // Toolbar buttons and styled links
                    document.querySelectorAll('button,.btn,a.btn,[type="button"],[type="submit"]').forEach(function(el) {
                        if (inChat(el) || !inViewport(el)) return;
                        var t = (el.innerText || el.value || '').trim().replace(/\s+/g,' ').substring(0,50);
                        if (t && !seen[t]) { seen[t]=1; struct.push('[Button] "' + t + '" (' + elPos(el) + ')'); }
                    });

                    // Dropdowns with all their options
                    document.querySelectorAll('select').forEach(function(el) {
                        if (inChat(el) || !inViewport(el)) return;
                        var opts = Array.from(el.options).map(function(o){ return o.text.trim(); }).filter(Boolean).join(' / ');
                        if (opts) struct.push('[Dropdown] options: "' + opts + '" (' + elPos(el) + ')');
                    });

                    // Text inputs
                    document.querySelectorAll('input[type="text"],input[type="search"],input:not([type])').forEach(function(el) {
                        if (inChat(el) || !inViewport(el)) return;
                        var lbl = el.placeholder || el.getAttribute('aria-label') || el.name || '';
                        if (lbl && !seen[lbl]) { seen[lbl]=1; struct.push('[Input] "' + lbl + '" (' + elPos(el) + ')'); }
                    });

                    // Table column headers
                    var thTexts = [];
                    document.querySelectorAll('th').forEach(function(el) {
                        if (inChat(el) || !inViewport(el)) return;
                        var t = el.innerText.trim().replace(/\s+/g,' ');
                        if (t) thTexts.push(t);
                    });
                    if (thTexts.length) struct.push('[TableColumns] ' + thTexts.join(' | '));

                    // Clickable links inside table cells (first row as example, max 3)
                    var tdLinkCount = 0;
                    var tdLinkExamples = [];
                    document.querySelectorAll('table td a, tbody td a').forEach(function(el) {
                        if (inChat(el) || !inViewport(el) || tdLinkCount >= 3) return;
                        var t = el.innerText.trim().replace(/\s+/g,' ');
                        if (t) { tdLinkExamples.push('"' + t + '"'); tdLinkCount++; }
                    });
                    if (tdLinkExamples.length) struct.push('[TableRowLinks] Clicking these opens the detail page: ' + tdLinkExamples.join(', ') + ' (same pattern for all rows)');

                    // Action buttons inside table cells (Edit/View/Delete etc.)
                    var tdBtnSeen = {};
                    document.querySelectorAll('table td button, table td .btn, table td a.btn, tbody td button, tbody td .btn').forEach(function(el) {
                        if (inChat(el) || !inViewport(el)) return;
                        var t = (el.innerText || el.title || el.getAttribute('data-original-title') || '').trim().replace(/\s+/g,' ').substring(0,30);
                        if (t && !tdBtnSeen[t]) { tdBtnSeen[t]=1; struct.push('[TableRowAction] "' + t + '" button available on each row'); }
                    });

                    if (struct.length) formData.append('page_structure', struct.join('\n'));
                } catch(stErr) { /* structure extraction failed */ }

                // Capture page screenshot for visual layout context
                try {
                    if (typeof html2canvas === 'function') {
                        var canvas = await html2canvas(document.body, {
                            scale: 0.85,
                            useCORS: true,
                            logging: false,
                            imageTimeout: 3000,
                            width: window.innerWidth,
                            height: window.innerHeight,
                            windowWidth: window.innerWidth,
                            windowHeight: window.innerHeight,
                            x: 0,
                            y: window.scrollY,
                            ignoreElements: function(el) {
                                return el.id === 'ai-chat-panel' || el.id === 'ai-chat-overlay' || el.id === 'ai-chat-fab';
                            }
                        });
                        var b64 = canvas.toDataURL('image/png').split(',')[1];
                        if (b64) {
                            formData.append('page_screenshot', b64);
                            formData.append('page_screenshot_type', 'image/png');
                        }
                    }
                } catch (ssErr) { /* screenshot failed — continue without it */ }

                // Show typing indicator
                $box.append(
                    '<div id="ai-typing" style="display:flex;margin-bottom:10px"><div style="padding:9px 13px;border-radius:16px 16px 16px 4px;background:#e9ecef;color:#999;font-size:13px"><i>Thinking...</i></div></div>'
                );
                $box.scrollTop($box[0].scrollHeight);
                $('#ai-send-btn').prop('disabled', true);

                $.ajax({
                    url: "{{ route('vendor.ai-chat.send') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#ai-typing').remove();
                        if (res.success) aiRenderMessage('assistant', res.message);
                        else alert(res.message || 'Something went wrong.');
                    },
                    error: function(xhr) {
                        $('#ai-typing').remove();
                        alert(xhr.responseJSON ? xhr.responseJSON.message :
                            'AI request failed.');
                    },
                    complete: function() {
                        $('#ai-send-btn').prop('disabled', false);
                    }
                });
            });

            $('#ai-clear-memory').on('click', function() {
                if (!confirm('Clear all AI chat memory?')) return;
                $.post("{{ route('vendor.ai-chat.clear') }}", {
                    _token: '{{ csrf_token() }}'
                }, function(res) {
                    if (res.success) $box.html(
                        '<div class="text-muted text-center" style="margin-top:40%">Chat cleared.</div>'
                    );
                });
            });

            // Enter to send, Shift+Enter for newline, auto-resize textarea
            $('#ai-message').on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    $('#ai-chat-form').submit();
                }
            }).on('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 80) + 'px';
            });
        });
    </script>
    <script>
        setInterval(() => {
            fetch('/heartbeat');
        }, 180000); // every 3 minutes
    </script>

</body>

</html>
