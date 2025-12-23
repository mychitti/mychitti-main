 @extends('layouts.vendor.app')

 @section('title', 'Webpage Settings')

 @push('css_or_js')
     <meta name="csrf-token" content="{{ csrf_token() }}">
     <style>
         .settings-wrapper {
             font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
             min-height: 100vh;
             background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
             padding: 60px 20px;
             position: relative;
             overflow: hidden;
         }

         .settings-wrapper::before {
             content: '';
             position: absolute;
             top: -50%;
             right: -50%;
             width: 100%;
             height: 100%;
             background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
             animation: pulse 15s ease-in-out infinite;
         }

         @keyframes pulse {

             0%,
             100% {
                 transform: scale(1);
                 opacity: 0.5;
             }

             50% {
                 transform: scale(1.1);
                 opacity: 0.8;
             }
         }

         .settings-card {
             position: relative;
             max-width: 850px;
             margin: 0 auto;
             background: rgba(255, 255, 255, 0.98);
             backdrop-filter: blur(20px);
             border-radius: 24px;
             padding: 48px;
             box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5),
                 0 0 0 1px rgba(255, 255, 255, 0.1);
             border: 1px solid rgba(255, 255, 255, 0.2);
         }

         .settings-header {
             margin-bottom: 40px;
             padding-bottom: 24px;
             border-bottom: 2px solid #e5e7eb;
         }

         .settings-title {
             font-size: 32px;
             font-weight: 700;
             background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
             -webkit-background-clip: text;
             -webkit-text-fill-color: transparent;
             background-clip: text;
             margin: 0 0 8px 0;
             letter-spacing: -0.5px;
         }

         .settings-subtitle {
             font-size: 15px;
             color: #64748b;
             margin: 0;
         }

         . {
             margin-bottom: 28px;
             position: relative;
         }

         .settings-label {
             display: flex;
             align-items: center;
             gap: 8px;
             font-size: 13px;
             font-weight: 600;
             color: #475569;
             margin-top: 10px;
             text-transform: uppercase;
             letter-spacing: 0.5px;
         }

         .settings-icon {
             font-size: 16px;
         }

         {{-- .form-control,
         .settings-textarea {
             width: 100%;
             padding: 14px 18px;
             font-size: 15px;
             line-height: 1.6;
             color: #0f172a;
             background: #f8fafc;
             border: 2px solid #e2e8f0;
             border-radius: 12px;
             transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
             font-family: inherit;
         } --}} .form-control:hover,
         .settings-textarea:hover {
             border-color: #cbd5e1;
             background: #ffffff;
         }

         .form-control:focus,
         .settings-textarea:focus {
             outline: none;
             border-color: #6366f1;
             background: #ffffff;
             box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1),
                 0 4px 6px -1px rgba(0, 0, 0, 0.1);
             transform: translateY(-1px);
         }

         .form-control::placeholder,
         .settings-textarea::placeholder {
             color: #94a3b8;
         }

         .settings-textarea {
             resize: vertical;
             min-height: 110px;
         }

         .settings-phone-section {
             margin-bottom: 28px;
             background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
             padding: 24px;
             border-radius: 16px;
             border: 1px solid #e2e8f0;
         }

         .settings-phone-header {
             display: flex;
             justify-content: space-between;
             align-items: center;
             margin-bottom: 16px;
         }

         .settings-phone-header .settings-label {
             margin-bottom: 0;
         }

         .settings-phone-item {
             display: flex;
             gap: 12px;
             margin-bottom: 12px;
             animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
         }

         @keyframes slideIn {
             from {
                 opacity: 0;
                 transform: translateX(-20px);
             }

             to {
                 opacity: 1;
                 transform: translateX(0);
             }
         }

         .settings-phone-item .form-control {
             flex: 1;
             background: #ffffff;
         }

         .settings-btn {
             display: inline-flex;
             align-items: center;
             justify-content: center;
             gap: 8px;
             padding: 11px 20px;
             font-size: 14px;
             font-weight: 600;
             line-height: 1.5;
             text-align: center;
             border: none;
             border-radius: 10px;
             cursor: pointer;
             transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
             position: relative;
             overflow: hidden;
         }

         .settings-btn::before {
             content: '';
             position: absolute;
             top: 50%;
             left: 50%;
             width: 0;
             height: 0;
             border-radius: 50%;
             background: rgba(255, 255, 255, 0.3);
             transform: translate(-50%, -50%);
             transition: width 0.6s, height 0.6s;
         }

         .settings-btn:hover::before {
             width: 300px;
             height: 300px;
         }

         .settings-btn span {
             position: relative;
             z-index: 1;
         }

         .settings-btn-primary {
             color: #ffffff;
             background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
             box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
         }

         .settings-btn-primary:hover {
             transform: translateY(-2px);
             box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
         }

         .settings-btn-primary:active {
             transform: translateY(0);
         }

         .settings-btn-danger {
             color: #ffffff;
             background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
             padding: 13px 20px;
             box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
         }

         .settings-btn-danger:hover {
             transform: translateY(-2px);
             box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
         }

         .settings-btn-danger:active {
             transform: translateY(0);
         }

         .settings-btn-success {
             width: 100%;
             padding: 16px 24px;
             font-size: 16px;
             color: #ffffff;
             background: linear-gradient(135deg, #10b981 0%, #059669 100%);
             margin-top: 16px;
             box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
         }

         .settings-btn-success:hover {
             transform: translateY(-3px);
             box-shadow: 0 15px 35px rgba(16, 185, 129, 0.4);
         }

         .settings-btn-success:active {
             transform: translateY(-1px);
         }

         @media (max-width: 640px) {
             .settings-wrapper {
                 padding: 30px 15px;
             }

             .settings-card {
                 padding: 32px 24px;
             }

             .settings-title {
                 font-size: 26px;
             }

             .settings-phone-item {
                 flex-direction: column;
             }

             .settings-btn-danger {
                 width: 100%;
             }
         }
     </style>
 @endpush

 @section('content')
     <div class="content container-fluid">
         <!-- Page Header -->
         <div class="page-header">
             <h1 class="page-header-title"><i class="tio-filter-list"></i>Webpage Settings</h1>
         </div>
     </div>
     <div class="row p-3 d-flex ">
         <div class="col-md-6 ">
             <form method="post" action="{{ route('vendor.settings.webpage-update') }}" id="settingsForm"
                 class=" card shadow p-2">
                 @csrf
                 <h3>Contact Settings</h3>
                 <div class="row ">
                     <!-- Website Name -->
                     <div class=" col-md-6">
                         <label class="settings-label">
                             <span class="settings-icon">🌐</span>
                             <span>Website Name</span>
                         </label>
                         <input type="text" id="websiteName" value="{{ $storeConfig?->webpage_name ?? $store->name }}"
                             name="website_name" class="form-control" placeholder="Enter your website name">
                     </div>

                     <!-- Email -->
                     <div class=" col-md-6">
                         <label class="settings-label">
                             <span class="settings-icon">✉️</span>
                             <span>Email Address</span>
                         </label>
                         <input type="email" id="email" value="{{ $storeConfig?->webpage_email ?? $store->email }}"
                             name="email" class="form-control" placeholder="contact@mail.com">
                     </div>

                     <!-- Address -->
                     <div class="col-md-12">
                         <label class="settings-label">
                             <span class="settings-icon">📍</span>
                             <span>Physical Address</span>
                         </label>
                         <textarea id="address" class="form-control" name="address" placeholder="Enter your complete address">{{ $storeConfig?->webpage_address ?? $store->address }}</textarea>
                     </div>
                 </div>

                 <!-- Phone Numbers -->
                 <div class="settings-phone-section mt-3">
                     <div class="settings-phone-header">
                         <label class="settings-label">
                             <span class="settings-icon">📞</span>
                             <span>Phone Numbers</span>
                         </label>
                         <button type="button" class="btn btn--primary" onclick="addPhoneNumber()">
                             <span>+</span>
                             <span>Add Phone</span>
                         </button>
                     </div>
                     <div id="phoneContainer" class="row">
                         @php
                             $phones = $storeConfig?->webpage_phones;
                             if ($phones) {
                                 $phones = json_decode($phones, true);
                             } else {
                                 $phones = [];
                             }
                         @endphp
                         @if (!empty($phones))
                             @foreach ($phones as $key => $value)
                                 <div class="settings-phone-item col-md-6">
                                     <input type="number" name="phone[]" value="{{ $value }}"
                                         class="form-control phone-input" placeholder="Phone Number">
                                 </div>
                             @endforeach
                         @else
                             <div class="settings-phone-item col-md-6">
                                 <input type="number" name="phone[]" value="{{ $store->phone }}"
                                     class="form-control phone-input" placeholder="Phone Number">
                             </div>
                         @endif
                     </div>
                 </div>
                 <div class="border p-2">
                     <div class="row">
                         <div class="col-md-6">
                             <label for="inventory_position"><strong>Inventory Items Display Position</strong></label>
                             <select name="inventory_items_position" id="inventory_position" class="form-control mt-2">
                                 <option {{ $storeConfig?->inventory_items_position == 'above' ? 'selected' : '' }}
                                     value="above">Above Service Section</option>
                                 <option {{ $storeConfig?->inventory_items_position == 'below' ? 'selected' : '' }}
                                     value="below">Below Service Section</option>
                             </select>
                         </div>
                         <div class="col-md-6 ">
                             <small class="badge badge-soft-primary" style="margin-top: -5px;">
                                 <i class="tio-info-outined"></i> Select where the inventory items should be displayed in
                                 relation to the service section
                                 on the Webpage. If you choose <strong>Above</strong>, inventory items will appear
                                 before the services. If you choose <strong>Below</strong>, they will appear after the
                                 service
                                 section.
                             </small>
                         </div>
                     </div>
                 </div>
                 <div class="mt-3">
                     <input type="hidden" name="latitude" id="latitude" value="{{ $storeConfig?->webpage_latitude ?? $store->latitude}}">
                     <input type="hidden" name="longitude" id="longitude" value="{{ $storeConfig?->webpage_longitude ?? $store->longitude }}">
                     {{-- <div class="invisible" style="height: 0px;">
                         <label class="form-label" for="latitude">{{ translate('messages.latitude') }}<span
                                 class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                 data-original-title="{{ translate('messages.store_lat_lng_warning') }}"><img
                                     src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                     alt="{{ translate('messages.store_lat_lng_warning') }}"></span></label>
                         <input type="text" id="latitude" name="latitude" class="form-control"
                             placeholder="{{ translate('messages.Ex:') }} -94.22213"
                             value="{{ $storeConfig?->webpage_latitude ?? $store->latitude }}" required readonly>
                     </div>
                     <div class="invisible" style="height: 0px;">
                         <label class="form-label" for="longitude">{{ translate('messages.longitude') }}<span
                                 class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                 data-original-title="{{ translate('messages.store_lat_lng_warning') }}"><img
                                     src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                     alt="{{ translate('messages.store_lat_lng_warning') }}"></span></label>
                         <input type="text" name="longitude" class="form-control"
                             placeholder="{{ translate('messages.Ex:') }} 103.344322" id="longitude"
                             value="{{ $storeConfig?->webpage_longitude ?? $store->longitude }}" required readonly>
                     </div> --}}
                     {{-- <input id="pac-input" class="controls rounded" data-toggle="tooltip" data-placement="right"
                         data-original-title="{{ translate('messages.search_your_location_here') }}" type="text"
                         placeholder="{{ translate('messages.search_here') }}" /> --}}
                         <h3>Map</h3>
                     <input type="text" id="searchInput" class="form-control">

                     <div id="map"></div>
                 </div>

                 <!-- Save Button -->
                 <div class="w-100 d-flex justify-content-end mt-3">
                     <button type="submit" class="btn btn--primary">
                         <span>💾</span>
                         <span>Save Contact Settings</span>
                     </button>
                 </div>
             </form>
         </div>
         <div class="col-md-6 ">


             <div class="accordion mb-2" id="accordionExample">
                 <div class="card">
                     <div class="card-header" id="headingOne">
                         <h2 class="mb-0">
                             <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse"
                                 data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                 <span class="card-header-icon">
                                     <img class="w--22" src="{{ asset('public/assets/admin/img/store.png') }}"
                                         alt="">
                                 </span> {{ translate('messages.store_meta_data') }}
                             </button>
                         </h2>
                     </div>

                     <div id="collapseOne" class="collapse show" aria-labelledby="headingOne"
                         data-parent="#accordionExample">
                         <div class="card-body p-1">
                             @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
                             @php($language = $language->value ?? null)
                             @php($defaultLang = 'en')
                             <form action="{{ route('vendor.business-settings.update-meta-data', [$store['id']]) }}"
                                 method="post" enctype="multipart/form-data" class="col-12">
                                 @csrf
                                 <div class="row g-2">
                                     <div class="col-lg-6">

                                         <div id="default-form">
                                             <div class="form-group">
                                                 <label class="input-label"
                                                     for="meta_title">{{ translate('messages.meta_title') }}
                                                 </label>
                                                 <input type="text" id="meta_title" name="meta_title"
                                                     class="form-control" value="{{ $store->meta_title }}"
                                                     placeholder="{{ translate('messages.meta_title') }}">
                                             </div>
                                             <input type="hidden" name="lang[]" value="default">
                                             <div class="form-group mb-0">
                                                 <label class="input-label"
                                                     for="meta_description">{{ translate('messages.meta_description') }}
                                                 </label>
                                                 <textarea type="text" id="meta_description" name="meta_description"
                                                     placeholder="{{ translate('messages.meta_description') }}" class="form-control min-h-90px ckeditor">{{ $store->meta_description }}</textarea>
                                             </div>
                                         </div>

                                     </div>
                                     <div class="col-lg-6">
                                         <div class="">
                                             <div class="card-header">
                                                 <h5 class="card-title">
                                                     <span class="card-header-icon mr-1"><i
                                                             class="tio-dashboard"></i></span>
                                                     <span>{{ translate('store_meta_image') }}</span>
                                                 </h5>
                                             </div>
                                             <div class="card-body">
                                                 <div class="d-flex flex-wrap flex-sm-nowrap __gap-12px">
                                                     <label class="__custom-upload-img mr-lg-5">
                                                         <label class="form-label">
                                                             {{ translate('meta_image') }} <span
                                                                 class="text--primary">({{ translate('1:1') }})</span>
                                                         </label>
                                                         <div class="text-center">
                                                             <img class="img--110 min-height-170px min-width-170px onerror-image"
                                                                 id="viewer"
                                                                 data-onerror-image="{{ asset('public/assets/admin/img/upload.png') }}"
                                                                 src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->meta_image, asset('storage/app/public/store') . '/' . $store->meta_image, asset('public/assets/admin/img/upload.png'), 'store/') }}"
                                                                 alt="{{ translate('meta_image') }}" />
                                                         </div>
                                                         <input type="file" name="meta_image" id="customFileEg1"
                                                             class="custom-file-input"
                                                             accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                     </label>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="col-12">
                                         <div class="justify-content-end btn--container">
                                             <button type="submit"
                                                 class="btn btn--primary">{{ translate('save_changes') }}</button>
                                         </div>
                                     </div>
                                 </div>
                             </form>
                         </div>
                     </div>

                 </div>


             </div>
             <div class="accordion" id="accordionExample2">

                 <div class="card">
                     <div class="card-header" id="headingTwo">
                         <h2 class="mb-0">
                             <button class="btn btn-link btn-block text-left collapsed" type="button"
                                 data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false"
                                 aria-controls="collapseTwo">
                                 <span class="card-header-icon">
                                     <img class="w--22" src="{{ asset('public/assets/admin/img/store.png') }}"
                                         alt="">
                                 </span> {{ translate('messages.Social Media Links') }}
                             </button>
                         </h2>
                     </div>
                     <div id="collapseTwo" class="collapse show" aria-labelledby="headingTwo"
                         data-parent="#accordionExample">
                         <div class="card-body p-1">
                             <form action="{{ route('vendor.business-settings.update-social-media', [$store['id']]) }}"
                                 method="post" enctype="multipart/form-data" class="col-12">
                                 @csrf
                                 <div class="row g-2">

                                     <div class="col-lg-12">
                                         <div class="">
                                             <div class="">
                                                 <div id="default-form">
                                                     <div class="form-group mb-0">

                                                         <div class="row">
                                                             <div class="input-group mb-3 col-md-6 ">
                                                                 <span class="input-group-text">Instagram</span>
                                                                 <input type="text" class="form-control"
                                                                     value="{{ $store->insta_url }}" name="insta_url"
                                                                     id="insta_inp">
                                                             </div>
                                                             <div class="input-group mb-3 col-md-6">
                                                                 <span class="input-group-text">Pinterest</span>
                                                                 <input type="text" class="form-control"
                                                                     value="{{ $store->pinterest_url }}"
                                                                     name="pinterest_url" id="pinterest_inp">
                                                             </div>
                                                             <div class="input-group mb-3 col-md-6">
                                                                 <span class="input-group-text">Facebook</span>
                                                                 <input type="text" class="form-control"
                                                                     value="{{ $store->fb_url }}" name="fb_url"
                                                                     id="fb_inp">
                                                             </div>
                                                             <div class="input-group mb-3 col-md-6">
                                                                 <span class="input-group-text">Twitter</span>
                                                                 <input type="text" class="form-control"
                                                                     value="{{ $store->twitter_url }}" name="twitter_url"
                                                                     id="twitter_inp">
                                                             </div>
                                                             <div class="input-group mb-3 col-md-6">
                                                                 <span class="input-group-text">LinkedIn</span>
                                                                 <input type="text" class="form-control"
                                                                     value="{{ $store->linkedin_url }}"
                                                                     name="linkedin_url" id="linkedin_inp">
                                                             </div>
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="col-12">
                                         <div class="justify-content-end btn--container">
                                             <button type="submit"
                                                 class="btn btn--primary">{{ translate('save_changes') }}</button>
                                         </div>
                                     </div>
                                 </div>
                             </form>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
     </div>
 @endsection

 @push('script_2')
     {{-- <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script> --}}
     <script>
         var map, marker, geocoder;

         function initMap() {
             var defaultLat = {{ $storeConfig?->webpage_latitude ?? $store->latitude }};
             var defaultLng = {{ $storeConfig?->webpage_longitude ?? $store->longitude }};

             loadMap(defaultLat, defaultLng);

         }

         function loadMap(latitude, longitude) {
             console.log("Map Loaded:", latitude, longitude);

             const location = {
                 lat: latitude,
                 lng: longitude,
             };

             map = new google.maps.Map(document.getElementById("map"), {
                 zoom: 15,
                 center: location,
             });

             marker = new google.maps.Marker({
                 position: location,
                 map: map,
                 draggable: true,
             });

             geocoder = new google.maps.Geocoder();

             updateLatLng(latitude, longitude);

             google.maps.event.addListener(marker, "dragend", function(event) {
                 updateLatLng(event.latLng.lat(), event.latLng.lng());
             });

             const input = document.getElementById("searchInput");
             const autocomplete = new google.maps.places.Autocomplete(input);

             autocomplete.addListener("place_changed", function() {
                 const place = autocomplete.getPlace();
                 if (!place.geometry) return alert("No details found!");

                 map.setCenter(place.geometry.location);
                 marker.setPosition(place.geometry.location);

                 updateLatLng(
                     place.geometry.location.lat(),
                     place.geometry.location.lng()
                 );
             });
         }

         function updateLatLng(lat, lng) {
             $("#latitude").val(lat);
             $("#longitude").val(lng);
         }
     </script>

     <!-- LOAD GOOGLE MAP ONLY ONCE -->
     <script
         src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&callback=initMap"
         async defer></script>

     <script>
         function addPhoneNumber() {
             const container = document.getElementById('phoneContainer');
             const phoneItem = document.createElement('div');
             phoneItem.className = 'settings-phone-item col-md-6';
             phoneItem.innerHTML = `
                <input type="number" name="phone[]" class="form-control phone-input" placeholder="Phone Number">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removePhoneNumber(this)">
                    <span><i class="tio-delete-outlined"></i></span>
                </button>
            `;
             container.appendChild(phoneItem);
         }

         function removePhoneNumber(button) {
             const phoneItems = document.querySelectorAll('.settings-phone-item');
             if (phoneItems.length > 1) {
                 button.parentElement.remove();
             }
         }
     </script>
 @endpush
