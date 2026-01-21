@extends('layouts.admin.app')

@section('title', translate('mcvendor_setup'))


@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title mr-3">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/business.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.mcvendor_settings') }}
                </span>
            </h1>
            @include('admin-views.mcvendor-settings.partials.nav-menu')
        </div> 
        <!-- End Page Header --> 

     
        <form action="{{ route('admin.business-settings.mcvendor-setup-update') }}" method="post" enctype="multipart/form-data">
            @csrf
            @php($name = \App\Models\BusinessSetting::where('key', 'business_name')->first())

            <div class="row g-3">
                <div class="col-lg-12">
                  
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3">
                                
                                    <div class="d-flex __gap-12px mt-4">
                                        <div class="__custom-upload-img mr-lg-5">
                                            @php($logo = \App\Models\BusinessSetting::where('key', 'mcvendor_logo')->first())
                                            @php($logo = $logo->value ?? '')
                                            <label class="form-label">
                                                {{ translate('main logo') }} <span class="text--primary">(
                                                    {{ translate('3:1') }} )</span>
                                            </label>
                                            <label class="text-center position-relative">
                                                <img class="img--vertical onerror-image image--border" id="viewer"
                                                    data-onerror-image="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($logo, asset('storage/app/public/business/') . '/' . $logo, asset('public/assets/admin/img/upload-img.png'), 'business/') }}"
                                                    alt="logo image" />
                                                <div class="icon-file-group">
                                                    <div class="icon-file">
                                                        <input type="file" name="mcvendor_logo" id="customFileEg1"
                                                            class="custom-file-input"
                                                            accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                        <i class="tio-edit"></i>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>

                                        <div class="__custom-upload-img">
                                            @php($icon = \App\Models\BusinessSetting::where('key', 'mcvendor_footer_logo')->first())
                                            @php($icon = $icon->value ?? '')
                                            <label class="form-label">
                                                {{ translate('Footer Logo') }} <span class="text--primary">(
                                                    {{ translate('1:1') }} )</span>
                                            </label>
                                            <label class="text-center position-relative">
                                                <img class="img--133 onerror-image image--border" id="iconViewer"
                                                    data-onerror-image="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($icon, asset('storage/app/public/business/') . '/' . $icon, asset('public/assets/admin/img/upload-img.png'), 'business/') }}"
                                                    alt="Fav icon" />
                                                <div class="icon-file-group">
                                                    <div class="icon-file">
                                                        <input type="file" name="mcvendor_footer_logo" id="favIconUpload"
                                                            class="custom-file-input"
                                                            accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                        <i class="tio-edit"></i>
                                                    </div>
                                                    <button class="btn action-btn btn-outline-danger">
                                                        <i class="tio-delete-outlined"></i>
                                                    </button>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                               <div class="btn--container justify-content-end mt-3">
                                {{-- <button type="reset" class="btn btn--reset">{{ translate('messages.reset') }}</button> --}}
                                <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}"
                                    class="btn btn--primary call-demo">{{ translate('save_information') }}</button>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
             
            </div>
        </form>
    </div>

@endsection

@push('script_2')
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&v=3.45.8">
    </script>
    <script>
        "use strict";
        $(document).on('ready', function() {
            @php($country = \App\Models\BusinessSetting::where('key', 'country')->first())

            @if ($country)
                $("#country option[value='{{ $country->value }}']").attr('selected', 'selected').change();
            @endif
        });

        @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
        @php($language = $language->value ?? null)
        let language = <?php echo $language; ?>;
        $('[id=language]').val(language);


        $(document).on('click', '.maintenance-mode', function() {
            @if (env('APP_MODE') == 'demo')
                toastr.warning('Sorry! You can not enable maintenance mode in demo!');
            @else
                Swal.fire({
                    title: '{{ translate('messages.Are you sure?') }}',
                    text: '{{ translate('messages.all_your_apps_and_customer_website_will_be_disabled_until_you_‘Turn_Off’ _maintenance_mode.') }}',
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
                            url: '{{ route('admin.maintenance-mode') }}',
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
                            },
                        });
                    } else {
                        location.reload();
                    }
                })
            @endif

        });


        function readURL(input, viewer) {
            if (input.files && input.files[0]) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    $('#' + viewer).attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function() {
            readURL(this, 'viewer');
        });

        $("#favIconUpload").change(function() {
            readURL(this, 'iconViewer');
        });
        $("#signUpload").change(function() {
            readURL(this, 'signUploadViewer');
        });


        $(document).on("keydown", "input", function(e) {
            if (e.which === 13) e.preventDefault();
        });
        $(".reset_sign_upload").on("click", function() {
            $('#signUpload').val('');
            $("#signUploadViewer").attr('src', '');
            $(".sign_status").val('not_uploaded');
        })
    </script>
@endpush
